<?php

namespace CultuurNet\UDB3SilexEntryAPI;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\Entry\Rsp;
use CultuurNet\UDB3\Event\Commands\Unlabel;
use CultuurNet\UDB3\EventNotFoundException;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\LinkType;
use CultuurNet\UDB3\XMLSyntaxException;
use CultuurNet\UDB3SilexEntryAPI\CommandHandler\EntryAPIEventCommandHandler;
use CultuurNet\UDB3SilexEntryAPI\Event\Commands\AddLink;
use CultuurNet\UDB3SilexEntryAPI\Event\Commands\ApplyTranslation;
use CultuurNet\UDB3SilexEntryAPI\Event\Commands\DeleteTranslation;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\ElementNotFoundException;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\SchemaValidationException;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\SuspiciousContentException;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\TooLargeException;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\TooManyItemsException;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\UnexpectedNamespaceException;
use CultuurNet\UDB3SilexEntryAPI\Exceptions\UnexpectedRootElementException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\String\String;

class EventController
{
    /**
     * @var RepositoryInterface
     */
    protected $eventRepository;

    /**
     * @var string
     */
    protected $entryapiLinkBaseUrl;

    public function __construct(RepositoryInterface $eventRepository, $entryapiLinkBaseUrl)
    {
        $this->eventRepository = $eventRepository;
        $this->entryapiLinkBaseUrl = $entryapiLinkBaseUrl;
    }

    public function translate(Request $request, $cdbid)
    {
        $callback = function () use ($request, $cdbid) {
            $repository = $this->eventRepository;

            if ($request->getContentType() !== 'form') {
                $rsp = rsp::error(
                    'UnexpectedFailure',
                    'Content-Type is not x-www-form-urlencoded.'
                );
                return $rsp;
            }

            if ($request->request->has('lang')) {
                $language = strtolower($request->request->get('lang'));
            } else {
                throw new InvalidArgumentException(
                    'Language code is required.'
                );
            }

            $title = null;
            if ($request->request->has('title')) {
                $title = new String($request->request->get('title'));
            }

            $shortDescription = null;
            if ($request->request->has('shortdescription')) {
                $shortDescription = new String(
                    $request->request->get('shortdescription')
                );
            }

            $longDescription = null;
            if ($request->request->has('longdescription')) {
                $longDescription = new String(
                    $request->request->get('longdescription')
                );
            }

            $eventId = new String($cdbid);

            $command = new ApplyTranslation(
                $eventId,
                new Language($language),
                $title,
                $shortDescription,
                $longDescription
            );

            $commandHandler = new EntryAPIEventCommandHandler($repository);
            $commandHandler->handle($command);

            return $this->createInfoResponseForEvent(
                $cdbid,
                'TranslationCreated'
            );
        };

        return $this->processEventRequest($callback);
    }

    public function deleteTranslation(Request $request, $cdbid)
    {
        $callback = function () use ($request, $cdbid) {
            $repository = $this->eventRepository;

            if ($request->query->has('lang')) {
                $language = strtolower($request->query->get('lang'));
            } else {
                throw new InvalidArgumentException(
                    'Language code is required.'
                );
            }

            $eventId = new String($cdbid);

            $command = new DeleteTranslation(
                $eventId,
                new Language($language)
            );

            $commandHandler = new EntryAPIEventCommandHandler($repository);
            $commandHandler->handle($command);

            return $this->createInfoResponseForEvent(
                $cdbid,
                'TranslationWithdrawn'
            );
        };

        return $this->processEventRequest($callback);
    }

    /**
     * @param string $cdbid
     * @param string $code
     * @return Rsp
     */
    protected function createInfoResponseForEvent($cdbid, $code)
    {
        $link = $this->entryapiLinkBaseUrl . $cdbid;
        $rsp = new Rsp('0.1', Rsp::LEVEL_INFO, $code, $link, null);

        return $rsp;
    }

    /**
     * @param Request $request
     * @param string $cdbid
     * @return Response
     */
    public function deleteKeyword(Request $request, $cdbid)
    {
        $label = new Label($request->query->get('keyword'));

        $callback = function () use ($cdbid, $label) {
            $command = new Unlabel($cdbid, $label);

            $repository = $this->eventRepository;

            $commandHandler = new EntryAPIEventCommandHandler($repository);
            $commandHandler->handle($command);

            return $this->createInfoResponseForEvent(
                $cdbid,
                'KeywordWithdrawn'
            );
        };

        return $this->processEventRequest($callback);
    }

    public function addLink(Request $request, $cdbid)
    {
        $callback = function () use ($request, $cdbid) {
            $repository = $this->eventRepository;

            if ($request->getContentType() !== 'form') {
                $rsp = rsp::error('UnexpectedFailure', 'Content-Type is not x-www-form-urlencoded.');
                return $rsp;
            }

            if ($request->request->has('lang')) {
                $language = strtolower($request->request->get('lang'));
            } else {
                throw new InvalidArgumentException(
                    'Language code is required.'
                );
            }

            if ($request->request->has('link')) {
                $link = strtolower($request->request->get('link'));
            } else {
                throw new InvalidArgumentException(
                    'Link is required.'
                );
            }

            if ($request->request->has('linktype')) {
                $linktype = strtolower($request->request->get('linktype'));
            } else {
                throw new InvalidArgumentException(
                    'Link type is required.'
                );
            }

            $title = null;
            if ($request->request->has('title')) {
                $title = new String($request->request->get('title'));
            }

            $copyright = null;
            if ($request->request->has('copyright')) {
                $copyright = new String($request->request->get('copyright'));
            }

            $subbrand = null;
            if ($request->request->has('subbrand')) {
                $subbrand = new String($request->request->get('subbrand'));
            }

            $description = null;
            if ($request->request->has('description')) {
                $description = new String($request->request->get('description'));
            }

            $eventId = new String($cdbid);

            $command = new AddLink(
                $eventId,
                new Language($language),
                new String($link),
                new LinkType($linktype),
                $title,
                $copyright,
                $subbrand,
                $description
            );

            $commandHandler = new EntryAPIEventCommandHandler($repository);
            $commandHandler->handle($command);
            $link = $this->entryapiLinkBaseUrl . $cdbid;
            $rsp = new Rsp('0.1', 'INFO', 'LinkCreated', $link, null);
            return $rsp;
        };

        return $this->processEventRequest($callback);
    }

    private function processEventRequest($callback)
    {
        $status = null;

        try {
            $rsp = $callback();

        } catch (TooLargeException $e) {
            $rsp = rsp::error('FileSizeTooLarge', $e->getMessage());
        } catch (XMLSyntaxException $e) {
            $rsp = rsp::error('XmlSyntaxError', $e->getMessage());
        } catch (ElementNotFoundException $e) {
            $rsp = rsp::error('ElementNotFoundError', $e->getMessage());
        } catch (UnexpectedNamespaceException $e) {
            $rsp = rsp::error('XmlSyntaxError', $e->getMessage());
        } catch (UnexpectedRootElementException $e) {
            $rsp = rsp::error('XmlSyntaxError', $e->getMessage());
        } catch (SchemaValidationException $e) {
            $rsp = rsp::error('XmlSyntaxError', $e->getMessage());
        } catch (TooManyItemsException $e) {
            $rsp = rsp::error('TooManyItems', $e->getMessage());
        } catch (SuspiciousContentException $e) {
            $rsp = rsp::error('SuspectedContent', $e->getMessage());
        } catch (EventNotFoundException $e) {
            $status = Response::HTTP_NOT_FOUND;
            $rsp = rsp::error('NotFound', 'Resource not found');
        } catch (\Exception $e) {
            $rsp = rsp::error('UnexpectedFailure', $e->getMessage());
        }

        return $this->createResponse($rsp, $status);
    }

    /**
     * @param Rsp $rsp
     * @return Response
     */
    private function createResponse(Rsp $rsp, $status = null)
    {
        $headers = array('Content-Type'=>'application/xml');
        $xml = $rsp->toXml();

        if (null === $status) {
            $status = $this->statusForRsp($rsp);
        }

        return new Response($xml, $status, $headers);
    }

    /**
     * @param Rsp $rsp
     * @return int
     */
    private function statusForRsp(Rsp $rsp)
    {
        if ($rsp->isError()) {
            return Response::HTTP_BAD_REQUEST;
        }

        return Response::HTTP_OK;
    }
}
