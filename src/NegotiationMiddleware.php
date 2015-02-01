<?php

namespace Crell\Stacker;

use Negotiation\Decoder\DecoderProvider;
use Negotiation\Decoder\DecoderProviderInterface;
use Negotiation\FormatNegotiator;
use Negotiation\FormatNegotiatorInterface;
use Negotiation\LanguageNegotiator;
use Negotiation\NegotiatorInterface;
use Phly\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * This class is a copy-paste-modify of Will Durand's Stack Negotiation library.
 * All credit for the actual work here goes to him.
 *
 * https://github.com/willdurand/StackNegotiation
 */
class NegotiationMiddleware implements HttpMiddlewareInterface
{

    /**
     * @var HttpMiddlewareInterface
     */
    private $app;

    /**
     * @var FormatNegotiatorInterface
     */
    private $formatNegotiator;

    /**
     * @var NegotiatorInterface
     */
    private $languageNegotiator;

    /**
     * @var DecoderProviderInterface
     */
    private $decoderProvider;

    /**
     * @var array
     */
    private $defaultOptions = [
      'format_priorities'   => [],
      'language_priorities' => [],
    ];

    /**
     * @var array
     */
    private $options;

    public function __construct(
      HttpMiddlewareInterface $app,
      FormatNegotiatorInterface $formatNegotiator = null,
      NegotiatorInterface $languageNegotiator     = null,
      DecoderProviderInterface $decoderProvider   = null,
      array $options = []
    ) {
        $this->app                = $app;
        $this->formatNegotiator   = $formatNegotiator   ?: new FormatNegotiator();
        $this->languageNegotiator = $languageNegotiator ?: new LanguageNegotiator();
        $this->decoderProvider    = $decoderProvider    ?: new DecoderProvider([
          'json' => new JsonEncoder(),
          'xml'  => new XmlEncoder(),
        ]);
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request)
    //public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // `Accept` header
        // Symfony version:
        // if (null !== $accept = $request->headers->get('Accept')) {
        // PSR-7 version:
        if (null !== $accept = $request->getHeader('Accept')) {
            $priorities = $this->formatNegotiator->normalizePriorities($this->options['format_priorities']);
            $accept     = $this->formatNegotiator->getBest($accept, $priorities);

            // Symfony version:
            //$request->attributes->set('_accept', $accept);
            // PSR-7 version:
            $request = $request->withAttribute('_accept', $accept);

            if (null !== $accept && !$accept->isMediaRange()) {
                // Symfony version:
                //$request->attributes->set('_mime_type', $accept->getValue());
                //$request->attributes->set('_format', $this->formatNegotiator->getFormat($accept->getValue()));
                // PSR-7 version:
                $request = $request
                  ->withAttribute('_mime_type', $accept->getValue())
                  ->withAttribute('_format', $this->formatNegotiator->getFormat($accept->getValue()));
            }
        }

        // `Accept-Language` header
        // Symfony version:
        // if (null !== $accept = $request->headers->get('Accept-Language')) {
        if (null !== $accept = $request->getHeader('Accept-Language')) {
            $accept = $this->languageNegotiator->getBest($accept, $this->options['language_priorities']);
            // Symfony version:
            //$request->attributes->set('_accept_language', $accept);
            // PSR-7 version:
            $request = $request->withAttribute('_accept_language', $accept);

            if (null !== $accept) {
                // Symfony version:
                // $request->attributes->set('_language', $accept->getValue());
                // PSR-7 version:
                $request = $request->withAttribute('_language', $accept->getValue());
            }
        }

        // Symfony version:
        /*
        try {
            // `Content-Type` header
            $this->decodeBody($request);
        } catch (BadRequestHttpException $e) {
            if (true === $catch) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
        */

        // PSR-7 version:
        $ret = $this->decodeBody($request);
        if (is_string($ret)) {
            return new Response(new StringStream($ret), 400);
        }
        else if ($ret instanceof ServerRequestInterface) {
            return $this->app->handle($ret);
        }
        else {
            return $this->app->handle($request);
        }
    }

    // Changed the type hint.
    // I'll be honest I don't entirely understand what this method is supposed to do. :-)
    private function decodeBody(ServerRequestInterface $request)
    {
        // This line doesn't change, neat. :-)
        if (in_array($request->getMethod(), [ 'POST', 'PUT', 'PATCH', 'DELETE' ])) {
            // Symfony version:
            // $contentType = $request->headers->get('Content-Type');
            // PSR-7 version:
            $contentType = $request->getHeader('Content-Type');
            $format      = $this->formatNegotiator->getFormat($contentType);

            if (!$this->decoderProvider->supports($format)) {
                return;
            }

            $decoder = $this->decoderProvider->getDecoder($format);
            // Symfony version:
            // $content = $request->getContent();
            // PSR-7 version: (Note that we need the whole body string anyway in order to determine its mime type this way.
            $content = $request->getBody()->getContents();

            if (!empty($content)) {
                try {
                    $data = $decoder->decode($content, $format);
                } catch (\Exception $e) {
                    $data = null;
                }

                if (is_array($data)) {
                    // Symfony version:
                    // $request->request->replace($data);
                    // PSR-7 version, I think:
                    $request = $request->withBodyParams($data);
                } else {
                    return 'Invalid ' . $format . ' message received';
                }

                return $request;
            }
        }
    }
}
