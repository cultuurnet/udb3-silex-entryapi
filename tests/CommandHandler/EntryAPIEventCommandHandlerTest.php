<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 06/10/15
 * Time: 10:49
 */
namespace CultuurNet\UDB3SilexEntryAPI\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreatedFromCdbXml;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromCdbXml;
use CultuurNet\UDB3\Event\Events\LabelsMerged;
use CultuurNet\UDB3\Event\Events\TranslationApplied;
use CultuurNet\UDB3\Event\Events\TranslationDeleted;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3SilexEntryAPI\Event\Commands\AddEventFromCdbXml;
use CultuurNet\UDB3SilexEntryAPI\Event\Commands\ApplyTranslation;
use CultuurNet\UDB3SilexEntryAPI\Event\Commands\DeleteTranslation;
use CultuurNet\UDB3SilexEntryAPI\Event\Commands\MergeLabels;
use CultuurNet\UDB3SilexEntryAPI\Event\Commands\UpdateEventFromCdbXml;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\SchemaValidationException;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\UnexpectedNamespaceException;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\UnexpectedRootElementException;
use CultuurNet\UDB3SilexEntryAPI\SizeLimitedEventXmlString;
use ValueObjects\String\String;

class EntryAPIEventCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var String
     */
    protected $id;

    /**
     * @var String
     */
    protected $namespaceUri;

    /**
     * @var EventCreatedFromCdbXml
     */
    protected $eventCreated;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->id = new String('004aea08-e13d-48c9-b9eb-a18f20e6d44e');
        $xml = new SizeLimitedEventXmlString(
            file_get_contents(__DIR__ . '/ValidWithCdbid.xml')
        );
        $this->namespaceUri = new String(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->eventCreated = new EventCreatedFromCdbXml(
            $this->id,
            $xml,
            $this->namespaceUri
        );
    }

    /**
     * @inheritdoc
     */
    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus
    ) {
        return new EntryAPIEventCommandHandler(
            new EventRepository($eventStore, $eventBus)
        );
    }

    /**
     * @test
     */
    public function it_validates_the_xml_namespace()
    {
        $xml = new SizeLimitedEventXmlString(
            file_get_contents(__DIR__ . '/InvalidNamespace.xml')
        );
        $addEventFromCdbXml = new AddEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(UnexpectedNamespaceException::class);

        $this->scenario->when($addEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_the_xml_namespace_for_update()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/InvalidNamespace.xml'));
        $updateEventFromCdbXml = new UpdateEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(UnexpectedNamespaceException::class);

        $this->scenario->when($updateEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_the_root_element()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/InvalidRootElement.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(UnexpectedRootElementException::class);

        $this->scenario->when($addEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_the_root_element_for_update()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/InvalidRootElement.xml'));
        $updateEventFromCdbXml = new UpdateEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(UnexpectedRootElementException::class);

        $this->scenario->when($updateEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_against_the_xml_schema()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/InvalidSchemaTitleMissing.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(SchemaValidationException::class);

        $this->scenario->when($addEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_against_the_xml_schema_for_update()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/InvalidSchemaTitleMissing.xml'));
        $updateEventFromCdbXml = new UpdateEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(SchemaValidationException::class);

        $this->scenario->when($updateEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_accepts_valid_cdbxml()
    {
        $id = new String('foo');
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/Valid.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($id, $xml);

        $this->scenario
            ->when($addEventFromCdbXml)
            ->then(
                [
                    new EventCreatedFromCdbXml(
                        $id,
                        $xml,
                        $this->namespaceUri
                    )
                ]
            );
    }

    /**
     * @test
     */
    public function it_accepts_valid_cdbxml_for_update()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/Valid.xml'));
        $updateEventFromCdbXml = new UpdateEventFromCdbXml($this->id, $xml);

        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->eventCreated
                ]
            )
            ->when($updateEventFromCdbXml)
            ->then(
                [
                    new EventUpdatedFromCdbXml(
                        $this->id,
                        $xml,
                        $this->namespaceUri
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_validates_too_many_events()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/TooManyEvents.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(\CultuurNet\UDB3SilexEntryAPI\Exceptions\TooManyItemsException::class);

        $this->scenario->when($addEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_no_event()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/NoEventAtAll.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(\CultuurNet\UDB3SilexEntryAPI\Exceptions\ElementNotFoundException::class);

        $this->scenario->when($addEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_when_there_is_no_element_at_all()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/NoEventButActor.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(\CultuurNet\UDB3SilexEntryAPI\Exceptions\ElementNotFoundException::class);

        $this->scenario->when($addEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_empty_xml()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/Empty.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(\CultuurNet\UDB3SilexEntryAPI\Exceptions\ElementNotFoundException::class);

        $this->scenario->when($addEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_validates_suspicious_content()
    {
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/ScriptTag.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($this->id, $xml);

        $this->setExpectedException(\CultuurNet\UDB3SilexEntryAPI\Exceptions\SuspiciousContentException::class);

        $this->scenario->when($addEventFromCdbXml);
    }

    /**
     * @test
     */
    public function it_creates_an_event_when_posting_xml_without_a_cdbid()
    {
        $id = new String('foo');
        $xml = new SizeLimitedEventXmlString(file_get_contents(__DIR__ . '/Valid.xml'));
        $addEventFromCdbXml = new AddEventFromCdbXml($id, $xml);

        $this->scenario
            ->when($addEventFromCdbXml)
            ->then(
                [
                    new EventCreatedFromCdbXml(
                        $id,
                        $xml,
                        $this->namespaceUri
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_merges_labels()
    {
        $labels = new LabelCollection(
            [
                new Label('keyword1', false),
                new Label('keyword2', true),
            ]
        );

        $mergeLabels = new MergeLabels(
            $this->id,
            $labels
        );

        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->eventCreated
                ]
            )
            ->when($mergeLabels)
            ->then(
                [
                    new LabelsMerged(
                        $this->id,
                        $labels
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_applies_a_translation()
    {
        $title = new String('Dizorkestra en concert');
        $shortDescription = new String(
            'Concert Dizôrkestra, un groupe qui.'
        );
        $longDescription = new String(
            'Concert Dizôrkestra, un groupe qui se montre inventif.'
        );

        $applyTranslation = new ApplyTranslation(
            $this->id,
            new Language('fr'),
            $title,
            $shortDescription,
            $longDescription
        );

        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->eventCreated,
                ]
            )
            ->when($applyTranslation)
            ->then(
                [
                    new TranslationApplied(
                        $this->id,
                        new Language('fr'),
                        $title,
                        $shortDescription,
                        $longDescription
                    )
                ]
            );
    }

    /**
     * @test
     */
    public function it_deletes_a_translation()
    {
        $deleteTranslation = new DeleteTranslation(
            $this->id,
            new Language('fr')
        );

        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->eventCreated
                ]
            )
            ->when($deleteTranslation)
            ->then(
                [
                    new TranslationDeleted(
                        $this->id,
                        new Language('fr')
                    )
                ]
            );
    }
}
