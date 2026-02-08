<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class UserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $username,
        public ?string $school_id = null,
        public ?string $school_class_id = null,
        public ?string $password = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->name,
            email: $request->email,
            username: $request->username,
            school_id: $request->school_id,
            school_class_id: $request->school_class_id,
            password: $request->password,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'school_id' => $this->school_id,
            'school_class_id' => $this->school_class_id,
            'password' => $this->password,
        ]);
    }
}
