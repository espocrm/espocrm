<?php

namespace Espo\Core\Mail\Mail;

use ArrayIterator;
use Countable;
use Iterator;
use Traversable;
use Zend\Loader\PluginClassLocator;

class Headers extends \Zend\Mail\Headers
{
    public static function fromString($string, $EOL = self::EOL)
    {
        $headers     = new static();
        $currentLine = '';

        // iterate the header lines, some might be continuations
        foreach (explode($EOL, $string) as $line) {
            // check if a header name is present
            if (preg_match('/^(?P<name>[\x21-\x39\x3B-\x7E]+):.*$/', $line, $matches)) {
                if ($currentLine) {
                    // a header name was present, then store the current complete line
                    $headers->addHeaderLine($currentLine);
                }
                $currentLine = trim($line);
            } elseif (preg_match('/^\s+.*$/', $line, $matches)) {
                // continuation: append to current line
                $currentLine .= $line;
            } elseif (preg_match('/^\s*$/', $line)) {
                // empty line indicates end of headers
                break;
            } else {
                // Line does not match header format!
                throw new Exception\RuntimeException(sprintf(
                    'Line "%s"does not match header format!',
                    $line
                ));
            }
        }
        if ($currentLine) {
            $headers->addHeaderLine($currentLine);
        }
        return $headers;
    }
}
