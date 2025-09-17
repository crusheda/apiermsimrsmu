<?php

namespace App\Hashing;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class CustomHasher implements HasherContract
{
    protected $privateKey = "KDFLDMSTHBWWSGCBH";

    protected function preprocess($value)
    {
        return hash_hmac('sha256', $value, hash('sha256', $this->privateKey, false), false);
    }

    public function make($value, array $options = [])
    {
        return password_hash($this->preprocess($value), PASSWORD_BCRYPT);
    }

    public function check($value, $hashedValue, array $options = [])
    {
        return password_verify($this->preprocess($value), $hashedValue);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, $options);
    }

    public function info($hashedValue)
    {
        return password_get_info($hashedValue);
    }
}
