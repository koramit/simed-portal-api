<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class ADUser
{
    use PortalCallable;

    public function getUser(string|int $id): array
    {
        $data = is_numeric($id)
            ? ['org_id' => $id]
            : ['login' => $id];

        return $this->makePost('user', $data);
    }

    public function authenticate(string $login, string $password): array
    {
        return $this->makePost('authenticate', [
            'login' => $login,
            'password' => $password,
        ]);
    }
}
