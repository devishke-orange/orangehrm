<?php

namespace OrangeHRM\Scim\Controller;

use Exception;
use Error;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Exception\NotImplementedException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Response;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Controller\Rest\V2\GenericRestController;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Framework\Http\Response as HttpResponse;
use OrangeHRM\Scim\Api\Validator\ScimValidator;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ScimRestController extends GenericRestController
{
    /**
     * @param HttpRequest $httpRequest
     * @return HttpResponse
     * @throws Exception
     */
    public function handle(HttpRequest $httpRequest): HttpResponse
    {
        $request = new Request($httpRequest);
        $this->init($request);
        $response = new HttpResponse();
        $response->headers->set(Response::CONTENT_TYPE_KEY, Response::CONTENT_TYPE_SCIM);

        try {
            $validationRule = $this->getValidationRule($request);
            if ($validationRule instanceof ParamRuleCollection) {
                ScimValidator::validate($request->getAllParameters(), $validationRule);
            }
            switch ($httpRequest->getMethod()) {
                case Request::METHOD_GET:
                    $response->setContent($this->handleGetRequest($request)->format());
                    break;
                case Request::METHOD_POST:
                    $response->setContent($this->handlePostRequest($request)->format());
                    $response->setStatusCode(SymfonyResponse::HTTP_CREATED);
                    break;
                case Request::METHOD_PUT:
                case Request::METHOD_PATCH:
                    $response->setContent($this->handlePutRequest($request)->format());
                    break;
                case Request::METHOD_DELETE:
                    $this->handleDeleteRequest($request);
                    $response->setContent("");
                    $response->setStatusCode(SymfonyResponse::HTTP_NO_CONTENT);
                    break;
                default:
                    throw new NotImplementedException();
            }
        } catch (NotImplementedException $e) {
            $this->getLogger()->info($e->getMessage());
            $this->getLogger()->error($e->getTraceAsString());

            $response->setContent(
                Response::formatError(
                    ['error' => ['status' => '501', 'message' => 'Not Implemented']]
                )
            );
            $response->setStatusCode(501);
        } catch (InvalidParamException $e) {
            $this->getLogger()->info($e->getMessage());
            $this->getLogger()->info($e->getTraceAsString());

            $response->setContent(
                Response::formatError(
                    [
                        'error' => [
                            'status' => '422',
                            'message' => $this->getI18NHelper()->transBySource($e->getMessage()),
                            'data' => $e->getNormalizedErrorBag()
                        ]
                    ]
                )
            );
            $response->setStatusCode(422);
        } catch (Exception|Error $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->error($e->getTraceAsString());

            $response->setContent(
                Response::formatError(
                    ['error' => ['status' => '500', 'message' => 'Unexpected Error Occurred']]
                )
            );
            $response->setStatusCode(500);
        }

        return $response;
    }
}
