<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;
use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class ADUserAPI
{
    use PortalCallable;

    public function getUser(string|int $id): ResponseDto
    {
        $data = is_numeric($id)
            ? ['org_id' => $id]
            : ['login' => $id];

        return $this->makePost('user', $data);
    }

    public function authenticate(string $login, string $password): ResponseDto
    {
        return $this->makePost('authenticate', [
            'login' => $login,
            'password' => $password,
        ]);
    }
}
