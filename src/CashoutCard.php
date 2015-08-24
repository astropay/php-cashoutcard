<?php

namespace Astropay;

class CashoutCard {

    private $secret;
    private $x_login;
    private $x_trans_key;
    private $x_amount;
    private $x_currency;
    private $x_email;
    private $x_name;
    private $x_document;
    private $api_url;
    private $response;

    public function __construct($envitonment) {
        if ($envitonment === \Astropay\Constants::ENV_SANDBOX) {
            $this->api_url = \Astropay\Constants::API_SANDBOX_URL;
        } else {
            $this->api_url = \Astropay\Constants::API_URL;
        }
        $this->api_url .= '/cashOut/sendCard';
    }

    public function setCredentials($login, $trans_key, $secret) {
        $this->x_login = $login;
        $this->x_trans_key = $trans_key;
        $this->secret = $secret;
    }

    public function setAmount($amount) {
        $this->x_amount = filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT);
    }

    public function setEmail($email) {
        $this->x_email = filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    public function setCurrency($currency) {
        $this->x_currency = filter_var($currency, FILTER_SANITIZE_ENCODED);
    }

    public function setName($name) {
        $this->x_name = filter_var($name, FILTER_SANITIZE_ENCODED);
    }

    public function setDocument($document) {
        $this->x_document = filter_var($document, FILTER_SANITIZE_ENCODED);
    }

    /**
     * 
     * @return boolean
     */
    public function sendCard() {
        try {

            $data = array(
                'x_login' => $this->x_login,
                'x_trans_key' => $this->x_trans_key,
                'x_amount' => $this->x_amount,
                'x_currency' => $this->x_currency,
                'x_email' => $this->x_email,
            );

            if (!empty($this->x_name) && !is_null($this->x_document)) {
                $data['x_name'] = $this->x_name;
                $data['x_document'] = $this->x_document;
            }

            $this->validateData($data);

            $data['x_control'] = $this->generateControlString();

            $curl = new \Curl\Curl();

            $curl->post($this->api_url, $data);
            if ($curl->error) {
                throw new CashoutCardServiceUnabailableException($curl->error_message);
            }

            $response = json_decode($curl->response);

            if (json_last_error() != JSON_ERROR_NONE) {
                throw new CashoutCardRejectedException($curl->message);
            }

            $this->validateResponseString($response);

            return true;
        } catch (CashoutCardInvalidParamException $e) {
            $this->r_message = $e->getMessage();
        } catch (CashoutCardInvalidSignatureException $e) {
            $this->r_message = $e->getMessage();
        } catch (CashoutCardServiceUnabailableException $e) {
            $this->r_message = $e->getMessage();
        } catch (CashoutCardRejectedException $e) {
            $this->r_message = $e->getMessage();
        } catch (\Exception $e) {
            $this->r_message = $e->getMessage();
        }

        return false;
    }

    public function getCode() {
        return $this->r_code;
    }

    public function getMessage() {
        return $this->r_message;
    }

    public function getResponse() {
        return $this->r_response;
    }

    public function getAuthCode() {
        return $this->r_auth_code;
    }

    public function getEmail() {
        return $this->r_email;
    }

    public function getAmount() {
        return $this->r_amount;
    }

    public function getCurrency() {
        return $this->r_currency;
    }

    public function getControl() {
        return $this->r_control;
    }

    /**
     * 
     * @param mixed $data
     * @throws CashoutCardException
     */
    private function validateData($data) {
        $definition = array(
            'x_email' => FILTER_VALIDATE_EMAIL,
            'x_amount' => FILTER_VALIDATE_FLOAT,
        );
        $filtered_data = filter_var_array($data, $definition);
        foreach ($filtered_data as $key => $value) {
            if ($value != $data[$key]) {
                throw new CashoutCardInvalidParamException('Invalid value ' . $key . '=' . $data[$key] . ' (' . $value . ')');
            }
        }
    }

    private function generateControlString() {
        return sha1($this->secret .
                number_format($this->x_amount, 2) .
                $this->x_currency .
                $this->x_email);
    }

    /**
     * 
     * @param mixed $data
     * @throws CashoutCardRejectedException
     * @throws CashoutCardInvalidSignatureException
     */
    private function validateResponseString($data) {

        $this->r_code = filter_var($data->code, FILTER_SANITIZE_ENCODED);
        $this->r_message = filter_var($data->message, FILTER_SANITIZE_ENCODED);
        $this->r_response = filter_var($data->response, FILTER_SANITIZE_ENCODED);

        if ($this->r_code != 200) {
            throw new CashoutCardRejectedException($this->r_message);
        }

        $this->r_id_cashout = filter_var($data->id_cashout, FILTER_SANITIZE_NUMBER_INT);
        $this->r_auth_code = filter_var($data->auth_code, FILTER_SANITIZE_NUMBER_INT);
        $this->r_email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
        $this->r_amount = filter_var($data->amount, FILTER_SANITIZE_NUMBER_FLOAT);
        $this->r_currency = filter_var($data->currency, FILTER_SANITIZE_ENCODED);
        $this->r_control = filter_var($data->control, FILTER_SANITIZE_ENCODED);

        $control_signature = sha1($this->secret .
                $this->r_id_cashout .
                $this->r_email .
                number_format($this->r_amount, 2) .
                $this->r_currency);

        if ($control_signature != $this->r_control) {
            throw new CashoutCardInvalidSignatureException('Invalid control string');
        }
    }

}

class CashoutCardInvalidParamException extends \Exception {

    public function __construct($message, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}

class CashoutCardInvalidSignatureException extends \Exception {

    public function __construct($message, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}

class CashoutCardServiceUnabailableException extends \Exception {

    public function __construct($message, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}

class CashoutCardRejectedException extends \Exception {

    public function __construct($message, $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
