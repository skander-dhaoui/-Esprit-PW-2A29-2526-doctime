<?php
declare(strict_types=1);

namespace App\Models;

final class User
{
    private int $id;
    private string $nom;
    private string $prenom;
    private string $email;
    private string $telephone;
    private string $password;
    private string $role;
    private string $statut;
    private ?string $adresse;
    private ?string $dateNaissance;
    private ?string $avatar;
    private ?string $facePhoto;
    private ?string $faceEncoding;
    private ?string $faceDescriptor;
    private string $createdAt;
    private string $derniereConnexion;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->nom = (string) ($data['nom'] ?? '');
        $this->prenom = (string) ($data['prenom'] ?? '');
        $this->email = (string) ($data['email'] ?? '');
        $this->telephone = (string) ($data['telephone'] ?? '');
        $this->password = (string) ($data['password'] ?? '');
        $this->role = (string) ($data['role'] ?? 'patient');
        $this->statut = (string) ($data['statut'] ?? 'actif');
        $this->adresse = $data['adresse'] ?? null;
        $this->dateNaissance = $data['date_naissance'] ?? null;
        $this->avatar = $data['avatar'] ?? null;
        $this->facePhoto = $data['face_photo'] ?? null;
        $this->faceEncoding = $data['face_encoding'] ?? null;
        $this->faceDescriptor = $data['face_descriptor'] ?? null;
        $this->createdAt = (string) ($data['created_at'] ?? '');
        $this->derniereConnexion = (string) ($data['derniere_connexion'] ?? '');
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function getDateNaissance(): ?string
    {
        return $this->dateNaissance;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getFacePhoto(): ?string
    {
        return $this->facePhoto;
    }

    public function getFaceEncoding(): ?string
    {
        return $this->faceEncoding;
    }

    public function getFaceDescriptor(): ?string
    {
        return $this->faceDescriptor;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getDerniereConnexion(): string
    {
        return $this->derniereConnexion;
    }

    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setPrenom(string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function setStatut(string $statut): void
    {
        $this->statut = $statut;
    }

    public function setAdresse(?string $adresse): void
    {
        $this->adresse = $adresse;
    }

    public function setDateNaissance(?string $dateNaissance): void
    {
        $this->dateNaissance = $dateNaissance;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function setFacePhoto(?string $facePhoto): void
    {
        $this->facePhoto = $facePhoto;
    }

    public function setFaceEncoding(?string $faceEncoding): void
    {
        $this->faceEncoding = $faceEncoding;
    }

    public function setFaceDescriptor(?string $faceDescriptor): void
    {
        $this->faceDescriptor = $faceDescriptor;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setDerniereConnexion(string $derniereConnexion): void
    {
        $this->derniereConnexion = $derniereConnexion;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'role' => $this->role,
            'statut' => $this->statut,
            'adresse' => $this->adresse,
            'date_naissance' => $this->dateNaissance,
            'avatar' => $this->avatar,
            'face_photo' => $this->facePhoto,
            'created_at' => $this->createdAt,
            'derniere_connexion' => $this->derniereConnexion,
        ];
    }
}
