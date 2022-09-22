<?php

namespace LimeSurvey\Api\Command\V1;

use Permission;
use QuestionGroup;
use Survey;
use Yii;
use LimeSurvey\Api\Command\CommandInterface;
use LimeSurvey\Api\Command\Request\Request;
use LimeSurvey\Api\Command\Response\Response;
use LimeSurvey\Api\Command\Response\Status\StatusSuccess;
use LimeSurvey\Api\Command\Response\Status\StatusError;
use LimeSurvey\Api\Command\Response\Status\StatusErrorNotFound;
use LimeSurvey\Api\Command\Response\Status\StatusErrorBadRequest;
use LimeSurvey\Api\Command\Response\Status\StatusErrorUnauthorised;
use LimeSurvey\Api\ApiSession;

class QuestionList implements CommandInterface
{
    /**
     * Run survey question list command.
     *
     * @access public
     * @param \LimeSurvey\Api\Command\Request\Request $request
     * @return \LimeSurvey\Api\Command\Response\Response
     */
    public function run(Request $request)
    {
        $sSessionKey = (string) $request->getData('sessionKey');
        $iSurveyID = (int) $request->getData('surveyID');
        $iGroupID = $request->getData('groupID');
        $sLanguage = $request->getData('language');

        $apiSession = new ApiSession();
        if ($apiSession->checkKey($sSessionKey)) {
            Yii::app()->loadHelper("surveytranslator");
            $iSurveyID = (int) $iSurveyID;
            $oSurvey = Survey::model()->findByPk($iSurveyID);

            if (empty($oSurvey)) {
                return new Response(
                    ['status' => 'Error: Invalid survey ID'],
                    new StatusErrorNotFound()
                );
            }

            if (
                Permission::model()
                ->hasSurveyPermission(
                    $iSurveyID,
                    'survey',
                    'read'
                )
            ) {
                if (is_null($sLanguage)) {
                    $sLanguage = $oSurvey->language;
                }

                if (
                    !array_key_exists(
                        $sLanguage,
                        getLanguageDataRestricted()
                    ) || !in_array($sLanguage, $oSurvey->allLanguages)
                ) {
                    return new Response(
                        ['status' => 'Error: Invalid language'],
                        new StatusErrorBadRequest()
                    );
                }

                if ($iGroupID != null) {
                    $iGroupID = (int) $iGroupID;
                    $oGroup = QuestionGroup::model()
                        ->findByPk($iGroupID);

                    if (empty($oGroup)) {
                        return new Response(
                            ['status' => 'Error: group not found'],
                            new StatusErrorNotFound()
                        );
                    }

                    if ($oGroup->sid != $oSurvey->sid) {
                        return new Response(
                            ['status' => 'Error: Mismatch in surveyid and groupid'],
                            new StatusErrorNotFound()
                        );
                    } else {
                        $aQuestionList = $oGroup->allQuestions;
                    }
                } else {
                    $aQuestionList = $oSurvey->allQuestions;
                }

                if (count($aQuestionList) == 0) {
                    return new Response(
                        ['status' => 'No questions found'],
                        new StatusSuccess()
                    );
                }

                foreach ($aQuestionList as $oQuestion) {
                    $L10ns = $oQuestion->questionl10ns[$sLanguage];
                    $aData[] = array_merge(
                        [
                            'id' => $oQuestion->primaryKey,
                            'question' => $L10ns->question,
                            'help' => $L10ns->help,
                            'language' => $sLanguage,
                        ],
                        $oQuestion->attributes
                    );
                }
                return new Response(
                    $aData,
                    new StatusSuccess()
                );
            } else {
                return new Response(
                    ['status' => 'No permission'],
                    new StatusErrorUnauthorised()
                );
            }
        } else {
            return new Response(
                ['status' => ApiSession::INVALID_SESSION_KEY],
                new StatusErrorUnauthorised()
            );
        }
    }
}
