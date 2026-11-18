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

    /**
     * The Portal sends a different subset per path: a lookup by login has no full_name_en,
     * department_name, office_name or email, and the ALT authentication fallback only
     * login, org_id, full_name, email and password_expires_in_days. Missing optional fields are null.
     */
    public static function fromPortal(array $data): static
    {
        return new static(
            $data['login'],
            $data['org_id'],
            $data['full_name'],
            $data['full_name_en'] ?? null,
            $data['document_id'] ?? null,
            $data['position_id'] ?? null,
            $data['position_name'] ?? null,
            $data['division_id'] ?? null,
            $data['division_name'] ?? null,
            $data['department_name'] ?? null,
            $data['office_name'] ?? null,
            $data['email'] ?? null,
            $data['password_expires_in_days'],
            $data['remark'] ?? null,
        );
    }
}
