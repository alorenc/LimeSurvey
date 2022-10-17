<?php

namespace ls\tests\unit\api\command\v1;

use Eloquent\Phony\Phpunit\Phony;
use Permission;
use Question;
use ls\tests\TestBaseClass;
use ls\tests\unit\api\command\mixin\AssertResponse;
use LimeSurvey\Api\Command\V1\QuestionPropertiesSet;
use LimeSurvey\Api\Command\Request\Request;
use LimeSurvey\Api\Command\Response\Status\StatusErrorBadRequest;
use LimeSurvey\Api\Command\Response\Status\StatusErrorNotFound;
use LimeSurvey\Api\Command\Response\Status\StatusErrorUnauthorised;
use LimeSurvey\Api\ApiSession;


/**
 * @testdox API command v1 QuestionPropertiesSet.
 */
class QuestionPropertiesSetTest extends TestBaseClass
{
    use AssertResponse;

    /**
     * @testdox Returns invalid session response (error unauthorised) if session key is not valid.
     */
    public function testQuestionListInvalidSession()
    {
        $request = new Request(array(
            'sessionKey' => 'not-a-valid-session-id',
            'questionID' => 'questionID',
            'questionData' => 'questionData',
            'language' => 'language'
        ));
        $response = (new QuestionPropertiesSet())->run($request);

        $this->assertResponseInvalidSession($response);
    }


    /**
     * @testdox Returns error not-found if question id is not valid.
     */
    public function testQuestionPropertiesGetInvalidQuestionId()
    {
        $request = new Request(array(
            'sessionKey' => 'mock',
            'questionID' => 'questionID',
            'questionData' => 'questionData',
            'language' => 'language'
        ));

        $mockApiSessionHandle = Phony::mock(ApiSession::class);
        $mockApiSessionHandle
            ->checkKey
            ->returns(true);
        $mockApiSession = $mockApiSessionHandle->get();

        $command = new QuestionPropertiesSet();
        $command->setApiSession($mockApiSession);

        $response = $command->run($request);

        $this->assertResponseStatus(
            $response,
            new StatusErrorNotFound()
        );

        $this->assertResponseDataStatus(
            $response,
            'Error: Invalid group ID'
        );
    }

    /**
     * @testdox Returns error unauthorised if user does not have permission.
     */
    public function testQuestionPropertiesSetNoPermission()
    {
        $request = new Request(array(
            'sessionKey' => 'mock',
            'questionID' => 'mock',
            'questionData' => 'questionData',
            'language' => 'language'
        ));

        $mockApiSessionHandle = Phony::mock(ApiSession::class);
        $mockApiSessionHandle
            ->checkKey
            ->returns(true);
        $mockApiSession = $mockApiSessionHandle->get();

        $mockModelQuestionHandle = Phony::mock(Question::class);
        $mockModelQuestion = $mockModelQuestionHandle->get();

        $mockModelPermissionHandle = Phony::mock(Permission::class);
        $mockModelPermissionHandle->hasSurveyPermission
            ->returns(false);
        $mockModelPermission = $mockModelPermissionHandle->get();

        $command = new QuestionPropertiesSet();
        $command->setApiSession($mockApiSession);
        $command->setPermissionModel($mockModelPermission);
        $command->setQuestionModel($mockModelQuestion);

        $response = $command->run($request);

        $this->assertResponseStatus(
            $response,
            new StatusErrorUnauthorised()
        );

        $this->assertResponseDataStatus(
            $response,
            'No permission'
        );
    }

    /**
     * @testdox Returns error bad request if invalid language specified.
     */
    public function testQuestionPropertiesSetInvalidLanguage()
    {
        $request = new Request(array(
            'sessionKey' => 'mock',
            'questionID' => 'mock',
            'questionData' => 'questionData',
            'language' => 'invalid'
        ));

        $mockApiSessionHandle = Phony::mock(ApiSession::class);
        $mockApiSessionHandle
            ->checkKey
            ->returns(true);
        $mockApiSession = $mockApiSessionHandle->get();

        $mockQuestionHandle = Phony::mock(Question::class);
        $mockQuestion = $mockQuestionHandle->get();

        $mockModelPermissionHandle = Phony::mock(Permission::class);
        $mockModelPermissionHandle->hasSurveyPermission
            ->returns(true);
        $mockModelPermission = $mockModelPermissionHandle->get();

        $command = new QuestionPropertiesSet();
        $command->setApiSession($mockApiSession);
        $command->setQuestionModel($mockQuestion);
        $command->setPermissionModel($mockModelPermission);

        $response = $command->run($request);

        $this->assertResponseStatus(
            $response,
            new StatusErrorBadRequest()
        );

        $this->assertResponseDataStatus(
            $response,
            'Error: Invalid language'
        );
    }

    /**
     * @testdox Returns error bad-request no data provided.
     */
    public function testQuestionPropertiesGetInvalidSettings()
    {
        $request = new Request(array(
            'sessionKey' => 'mock',
            'questionID' => 'mock',
            'questionData' => array(),
            'language' => 'en'
        ));

        $mockApiSessionHandle = Phony::mock(ApiSession::class);
        $mockApiSessionHandle
            ->checkKey
            ->returns(true);
        $mockApiSession = $mockApiSessionHandle->get();

        $mockQuestionHandle = Phony::mock(Question::class);
        $mockQuestion = $mockQuestionHandle->get();

        $mockModelPermissionHandle = Phony::mock(Permission::class);
        $mockModelPermissionHandle->hasSurveyPermission
            ->returns(true);
        $mockModelPermission = $mockModelPermissionHandle->get();

        $command = new QuestionPropertiesSet();
        $command->setApiSession($mockApiSession);
        $command->setQuestionModel($mockQuestion);
        $command->setPermissionModel($mockModelPermission);

        $response = $command->run($request);

        $this->assertResponseStatus(
            $response,
            new StatusErrorBadRequest()
        );

        $this->assertResponseDataStatus(
            $response,
            'No valid Data'
        );
    }
}
