<?php

namespace Espo\Core\Utils\Api;


class Slim extends \Slim\Slim
{

	/**
     * Redefine the run method
     *
     * We no need to use a Slim handler      
     */
	public function run()
    {
        //set_error_handler(array('\Slim\Slim', 'handleErrors')); //Espo: no needs to use this handler

        //Apply final outer middleware layers
        if ($this->config('debug')) {
            //Apply pretty exceptions only in debug to avoid accidental information leakage in production
            //$this->add(new \Slim\Middleware\PrettyExceptions()); //Espo: no needs to use this handler 
        }

        //Invoke middleware and application stack
        $this->middleware[0]->call();

        //Fetch status, header, and body
        list($status, $headers, $body) = $this->response->finalize();

        // Serialize cookies (with optional encryption)
        \Slim\Http\Util::serializeCookies($headers, $this->response->cookies, $this->settings);

        //Send headers
        if (headers_sent() === false) {
            //Send status
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', \Slim\Http\Response::getMessageForCode($status)));
            } else {
                header(sprintf('HTTP/%s %s', $this->config('http.version'), \Slim\Http\Response::getMessageForCode($status)));
            }

            //Send headers
            foreach ($headers as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal) {
                    header("$name: $hVal", false);
                }
            }
        }

        //Send body, but only if it isn't a HEAD request
        if (!$this->request->isHead()) {
            echo $body;
        }

        //restore_error_handler();
    }


}