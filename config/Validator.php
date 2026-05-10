<?php
class Validator {
    private array $errors = [];

    public function getErrors(): array {
        return $this->errors;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    public function required(string $field, mixed $value, string $label): self {
        if ($value === null || trim((string)$value) === '') {
            $this->errors[$field] = "Le champ « $label » est obligatoire.";
        }
        return $this;
    }

    public function minLength(string $field, string $value, int $min, string $label): self {
        if (!isset($this->errors[$field]) && mb_strlen(trim($value)) < $min) {
            $this->errors[$field] = "Le champ « $label » doit contenir au moins $min caractères.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max, string $label): self {
        if (!isset($this->errors[$field]) && mb_strlen(trim($value)) > $max) {
            $this->errors[$field] = "Le champ « $label » ne doit pas dépasser $max caractères.";
        }
        return $this;
    }

    public function email(string $field, string $value, string $label): self {
        if (!isset($this->errors[$field]) && trim($value) !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Le champ « $label » doit être une adresse e-mail valide.";
        }
        return $this;
    }

    public function url(string $field, string $value, string $label): self {
        if (!isset($this->errors[$field]) && trim($value) !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "Le champ « $label » doit être une URL valide (ex : https://exemple.com).";
        }
        return $this;
    }

    public function numeric(string $field, mixed $value, string $label): self {
        if (!isset($this->errors[$field]) && trim((string)$value) !== '' && !is_numeric($value)) {
            $this->errors[$field] = "Le champ « $label » doit être un nombre.";
        }
        return $this;
    }

    public function positiveNumber(string $field, mixed $value, string $label): self {
        $this->numeric($field, $value, $label);
        if (!isset($this->errors[$field]) && (float)$value <= 0) {
            $this->errors[$field] = "Le champ « $label » doit être un nombre positif.";
        }
        return $this;
    }

    public function integer(string $field, mixed $value, string $label): self {
        if (!isset($this->errors[$field]) && trim((string)$value) !== '' && !ctype_digit((string)$value)) {
            $this->errors[$field] = "Le champ « $label » doit être un entier positif.";
        }
        return $this;
    }

    public function date(string $field, string $value, string $label, string $format = 'Y-m-d'): self {
        if (!isset($this->errors[$field]) && trim($value) !== '') {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field] = "Le champ « $label » doit être une date valide (format : $format).";
            }
        }
        return $this;
    }

    public function dateAfter(string $field, string $value, string $after, string $label, string $labelAfter): self {
        if (!isset($this->errors[$field]) && trim($value) !== '' && trim($after) !== '') {
            $d1 = DateTime::createFromFormat('Y-m-d', $value);
            $d2 = DateTime::createFromFormat('Y-m-d', $after);
            if ($d1 && $d2 && $d1 <= $d2) {
                $this->errors[$field] = "Le champ « $label » doit être postérieur à « $labelAfter ».";
            }
        }
        return $this;
    }

    public function dateNotPast(string $field, string $value, string $label): self {
        if (!isset($this->errors[$field]) && trim($value) !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $value);
            if ($d && $d < new DateTime('today')) {
                $this->errors[$field] = "Le champ « $label » ne peut pas être une date passée.";
            }
        }
        return $this;
    }

    public function inArray(string $field, mixed $value, array $allowed, string $label): self {
        if (!isset($this->errors[$field]) && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "La valeur du champ « $label » est invalide.";
        }
        return $this;
    }

    public function phone(string $field, string $value, string $label): self {
        if (!isset($this->errors[$field]) && trim($value) !== '') {
            $cleaned = preg_replace('/[\s\-\.\(\)\+]/', '', $value);
            if (!preg_match('/^\d{8,15}$/', $cleaned)) {
                $this->errors[$field] = "Le champ « $label » doit être un numéro de téléphone valide.";
            }
        }
        return $this;
    }
}
