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
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function setDateNaissance(?string $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function setFacePhoto(?string $facePhoto): self
    {
        $this->facePhoto = $facePhoto;
        return $this;
    }

    public function setFaceEncoding(?string $faceEncoding): self
    {
        $this->faceEncoding = $faceEncoding;
        return $this;
    }

    public function setFaceDescriptor(?string $faceDescriptor): self
    {
        $this->faceDescriptor = $faceDescriptor;
        return $this;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setDerniereConnexion(string $derniereConnexion): self
    {
        $this->derniereConnexion = $derniereConnexion;
        return $this;
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
