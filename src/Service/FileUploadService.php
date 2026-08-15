<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MAX_SIZE = 5 * 1024 * 1024; // 5MB

    public function upload(UploadedFile $file, string $uploadDirectory): string
    {
        // Vérifier l'extension
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \InvalidArgumentException('Format non autorisé. Formats acceptés : PDF, JPG, PNG');
        }

        // Vérifier la taille
        if ($file->getSize() > self::MAX_SIZE) {
            throw new \InvalidArgumentException('Fichier trop volumineux (max 5MB)');
        }

        // Générer nom sécurisé
        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

        try {
            $file->move($uploadDirectory, $fileName);
        } catch (FileException $e) {
            throw new \RuntimeException('Erreur lors de l\'upload : ' . $e->getMessage());
        }

        return $fileName;
    }
}
