<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Sulao\BaiduBos\Client;

class ClientMock extends Client
{
    public function getObjectAcl($path, array $options = [])
    {
        throw new Exception('', 404);
    }
}
