<?php
/**
 * Core plugin for LimeSurvey : password requirement settings
 * @version 1.1.0
 */

class PasswordRequirement extends \LimeSurvey\PluginManager\PluginBase
{
     /**
     * Where to save plugin settings etc.
     * @var string
     */
    protected $storage = 'DbStorage';

    /** @inheritdoc, this plugin didn't have any public method */
    public $allowedPublicMethods = array();

    protected $settings = [
        'adminPart' => array(
            'content' => 'Password requirements for administration login',
            'type' => 'info',
            'class' => "h3",
            'controlOptions' => array(
                'class' => "col-md-offset-4 col-md-6"
            ),
        ),
        'needsNumber' => array(
            'label' => 'Require at least one digit',
            'type' => 'checkbox',
            'default' => true,
        ),
        'needsUppercase' => array(
            'label' => 'Require at least one uppercase character',
            'type' => 'checkbox',
            'default' => true,
        ),
        'needsNonAlphanumeric' => array(
            'label' => 'Require at least one special character',
            'type' => 'checkbox',
            'default' => false,
        ),
        'minimumSize' => array(
            'label' => 'Minimum password length',
            'type' => 'int',
            'default' => 12,
        ),
        'surveyPart' => array(
            'content' => 'Password requirements for “Save and return later” feature',
            'type' => 'info',
            'class' => "h3",
            'controlOptions' => array(
                'class' => "col-md-offset-4 col-md-6"
            ),
            'type' => 'info',
        ),
        'surveySaveActive' => array(
            'type' => 'boolean',
            'label' => 'Check password when saving survey',
            'default' => false,
        ),
        'surveySaveNeedsNumber' => array(
            'label' => 'Require at least one digit',
            'type' => 'checkbox',
            'default' => false,
        ),
        'surveySaveNeedsUppercase' => array(
            'label' => 'Require at least one uppercase character',
            'type' => 'checkbox',
            'default' => false,
        ),
        'surveySaveNeedsNonAlphanumeric' => array(
            'label' => 'Require at least one special character',
            'type' => 'checkbox',
            'default' => false,
        ),
        'surveySaveMinimumSize' => array(
            'label' => 'Minimum password length',
            'type' => 'int',
            'default' => 8,
        ),
    ];
    /**
     * @return void
     */
    public function init()
    {
        $this->subscribe('checkPasswordRequirement');
        $this->subscribe('createRandomPassword');

        $this->subscribe('saveSurveyForm', 'validateSaveSurveyForm');
    }

    public function checkPasswordRequirement()
    {
        $oEvent = $this->getEvent();
        $password = $oEvent->get('password');
        $errors = $this->checkValidityOfPassword(
            $password,
            $this->get('needsNumber', null, null, true),
            $this->get('needsUppercase', null, null, true),
            $this->get('needsNonAlphanumeric', null, null, false),
            $this->get('minimumSize', null, null, 12)
        );
        if ($errors) {
            $oEvent->set('passwordOk', false);
            $oEvent->set('passwordError', $errors[0]); // Previous system
            $oEvent->set('passwordErrors', $errors);
        }
    }

    /** @see event 
     * get the current save action password and add errors if needed
     * @return void
     * */
    public function validateSaveSurveyForm()
    {
        if (!$this->get('surveySaveActive', null, null, false)) {
            return;
        }
        $event = $this->getEvent();
        if ($event->get('state') != 'validate') {
            // Action only when validate
            return;
        }
        $saveData = $event->get('saveData');
        $aSaveErrors = $event->get('aSaveErrors');
        if (empty($saveData['clearpassword'])) {
            // No need to check password if empty : core disallow it
            return;
        }

        $password = $saveData['clearpassword'];
        $errors = $this->checkValidityOfPassword(
            $password,
            $this->get('surveySaveNeedsNumber', null, null, false),
            $this->get('surveySaveNeedsUppercase', null, null, false),
            $this->get('surveySaveNeedsNonAlphanumeric', null, null, false),
            $this->get('surveySaveMinimumSize', null, null, 8)
        );
        if (empty($errors)) {
            return;
        }
        $event->append('aSaveErrors', $errors);
    }

    /**
     * Chek the validity of a pasword according to option
     * @param string $password
     * @param boolean $needsNumber
     * @param boolean $needsUppercase
     * @param boolean $needsNonAlphanumeric
     * @return null|array, null mean no issue.
     */
    private function checkValidityOfPassword($password, $needsNumber, $needsUppercase, $needsNonAlphanumeric, $minimumSize = 8)
    {
        $errors = [];
        if ($needsNumber && preg_match('/\d/', $password) === 0) {
            $errors[] = gT('The password does require at least one digit');
        }
        if ($needsUppercase && strtolower($password) == $password) {
            $errors[] = gT('The password does require at least one uppercase character');
        }
        if ($needsNonAlphanumeric && ctype_alnum($password)) {
            $errors[] = gT('The password does require at least one special character');
        }
        if ($minimumSize && strlen($password) < $minimumSize) {
            $errors[] = sprintf(gT('The password does not reach the minimum length of %s characters'), $minimumSize);
        }
        if (empty($errors)) {
            return null;
        }
        return $errors;
    }

    public function createRandomPassword()
    {
        $oEvent = $this->getEvent();
        $targetSize = $oEvent->get('targetSize', 8);

        $targetSize = $targetSize < $this->get('minimumSize', null, null, 12) ? $this->get('minimumSize', null, null, 12) : $targetSize;
        $uppercase = $this->get('needsUppercase', null, null, true);
        $numeric = $this->get('needsNumber', null, null, true);
        $nonAlpha = $this->get('needsNonAlphanumeric', null, null, false);

        $randomPassword = $this->getRandomString($targetSize, $uppercase, $numeric, $nonAlpha);
        
        $oEvent->set('password', $randomPassword);
    }

      /**
     * Provides meta data on the plugin settings that are available for this plugin.
     * This does not include enable / disable; a disabled plugin is never loaded.
     *
     */
    public function getPluginSettings($getValues = true)
    {
        $settings = parent::getPluginSettings($getValues);
        $settings['adminPart']['content'] = $this->gT("Password requirements for administration login");
        $settings['needsNumber']['label'] = $this->gT("Require at least one digit");
        $settings['needsUppercase']['label'] = $this->gT("Require at least one uppercase character");
        $settings['needsNonAlphanumeric']['label'] = $this->gT("Require at least one special character");
        $settings['minimumSize']['label'] = $this->gT("Minimum password length");
        $settings['surveyPart']['content'] = $this->gT("Password requirements for “Save and return later” feature");
        $settings['surveySaveActive']['label'] = $this->gT("Check password when use “Save and return later” feature");
        $settings['surveySaveNeedsNumber']['label'] = $this->gT("Require at least one digit");
        $settings['surveySaveNeedsUppercase']['label'] = $this->gT("Require at least one uppercase character");
        $settings['surveySaveNeedsNonAlphanumeric']['label'] = $this->gT("Require at least one special character");
        $settings['surveySaveMinimumSize']['label'] = $this->gT("Minimum password length");
        return $settings;
    }

    private function getRandomString($length = 8, $uppercase = false, $numeric = false, $nonAlpha = false)
    {
        // Init
        $str = '';

        /**
         * For each required character set:
         * - Add one character to the output string.
         * - Add the char set to the general char pool.
         */

        // Lowercase (always on)
        $chars = "abcdefghijklmnopqrstuvwxyz";
        $str .= pickRandomChar($chars);

        // Uppercase if applies
        // Add one character and also add the charset to the pool of available chars
        if ($uppercase) {
            $uppercase_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $str .= pickRandomChar($uppercase_chars);
            $chars .= $uppercase_chars;
        }

        // Numeric if applies
        // Add one character and also add the charset to the pool of available chars
        if ($numeric) {
            $numeric_chars = '0123456789';
            $str .= pickRandomChar($numeric_chars);
            $chars .= $numeric_chars;
        }

        // NonAlpha if applies
        // Add one character and also add the charset to the pool of available chars
        if ($nonAlpha) {
            $nonAlpha_chars = '-=!@#$%&*_+,.?;:';
            $str .= pickRandomChar($nonAlpha_chars);
            $chars .= $nonAlpha_chars;
        }

        // Trim in case length is less than the already appended characters
        if (strlen($str) > $length) {
            $str = substr($str, 0, $length);
        }

        /**
         * Pick remaning characters from the general char pool
         */

        // Fill string from general char pool
        for ($i = strlen($str); $i < $length; $i++) {
            $str .= pickRandomChar($chars);
        }

        /**
         * Wrap up
         */
        
        // Shuffle, as to not have always to start with the loweracse, then uppercase, ...
        $str = str_shuffle($str);

        return $str;
    }
}
