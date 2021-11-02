<?php
declare(strict_types=1);

namespace Seven\CloudflareGeoRedirect;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RedirectMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\InjectConfiguration(path="rules")
     * @var array|null
     */
    protected ?array $rules;

    /**
     * @Flow\Inject
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        if ($this->rules === null || empty($this->rules) === true || $request->hasHeader('CF-IPCountry') === false) {
            return $next->handle($request);
        }

        $countryHeader = $request->getHeader('CF-IPCountry')[0];

        foreach ($this->rules as $rule) {
            if ($rule['country'] === $countryHeader &&
                $request->getUri()->getPath() === $rule['matchUriPath']) {
                $response = $this->responseFactory->createResponse($rule['redirectStatusCode']);

                return $response->withHeader('Location', $rule['redirectUrl'])
                    ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
                    ->withHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
            }
        }

        return $next->handle($request);
    }
}
