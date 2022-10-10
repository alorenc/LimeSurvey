<?php

namespace ls\tests\unit\api\command\v1;

use ls\tests\TestBaseClass;
use ls\tests\unit\api\command\mixin\AssertInvalidSession;
use LimeSurvey\Api\Command\V1\QuestionGroupDelete;
use LimeSurvey\Api\Command\Request\Request;

/**
 * Tests for the API command v1 QuestionGroupDelete.
 */
class QuestionGroupDeleteTest extends TestBaseClass
{
    use AssertInvalidSession;

    public function testQuestionGroupDeleteInvalidSession()
    {
        $request = new Request(array(
            'sessionKey' => 'not-a-valid-session-id',
            'groupID' => 'groupID'
        ));
        $response = (new QuestionGroupDelete)->run($request);

        $this->assertInvalidSession($response);
    }
}
