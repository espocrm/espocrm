<?php

namespace Espo\Core\Mail\Account\Storage;

/**
 * Handle storage parameters.
 * To be used by extensions.
 *
 * @since 9.3.0
 */
interface Handler
{
    public function handle(Params $params, string $id): Params;
}
