<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

readonly class ADUserDto
{
    public function __construct(
        public string $login,
        public string $org_id,
        public string $full_name,
        public ?string $full_name_en,
        public ?string $document_id,
        public ?int $position_id,
        public ?string $position_name,
        public ?int $division_id,
        public ?string $division_name,
        public ?string $department_name,
        public ?string $office_name,
        public ?string $email,
        public int $password_expires_in_days,
        public ?string $remark,
    ) {}

    public static function fromPortal(array $data): static
    {
        return new static(
            $data['login'],
            $data['org_id'],
            $data['full_name'],
            $data['full_name_en'],
            $data['document_id'],
            $data['position_id'],
            $data['position_name'],
            $data['division_id'],
            $data['division_name'],
            $data['department_name'],
            $data['office_name'],
            $data['email'],
            $data['password_expires_in_days'],
            $data['remark'],
        );
    }
}
