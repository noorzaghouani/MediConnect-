<?php

namespace App\Tests\Unit\Service;

use App\Service\FileUploadService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests unitaires — FileUploadService
 *
 * Vérifie que le service d'upload rejette correctement
 * les fichiers invalides (extension, MIME, taille).
 */
class FileUploadServiceTest extends TestCase
{
    private FileUploadService $service;

    protected function setUp(): void
    {
        $this->service = new FileUploadService();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Crée un fichier temporaire réel et retourne un UploadedFile mocké
     * dont on contrôle l'extension, le MIME et la taille.
     */
    private function makeUploadedFile(
        string $originalName,
        string $mimeType,
        int    $size,
        string $guessedExtension
    ): UploadedFile {
        // Fichier physique temporaire requis par UploadedFile
        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tmpPath, str_repeat('A', min($size, 1024)));

        $mock = $this->getMockBuilder(UploadedFile::class)
            ->setConstructorArgs([$tmpPath, $originalName, $mimeType, null, true])
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize'])
            ->getMock();

        $mock->method('guessExtension')->willReturn($guessedExtension);
        $mock->method('getClientOriginalExtension')->willReturn(pathinfo($originalName, PATHINFO_EXTENSION));
        $mock->method('getMimeType')->willReturn($mimeType);
        $mock->method('getSize')->willReturn($size);

        return $mock;
    }

    // -------------------------------------------------------------------------
    // Tests — extensions autorisées
    // -------------------------------------------------------------------------

    /**
     * @testdox Un fichier PDF valide doit être accepté sans exception
     */
    public function testUploadAcceptsValidPdfExtension(): void
    {
        $file = $this->makeUploadedFile('diplome.pdf', 'application/pdf', 1024, 'pdf');

        // On s'attend à ce que move() ne soit pas appelé (mock partiel) →
        // on teste seulement que la validation ne lève pas d'exception.
        // Pour éviter l'appel réel à move(), on remocke move() pour ne rien faire.
        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize', 'move'])
            ->getMock();

        $file->method('guessExtension')->willReturn('pdf');
        $file->method('getClientOriginalExtension')->willReturn('pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(1024);
        $file->method('move')->willReturnSelf();

        // Aucune exception ne doit être levée
        $this->expectNotToPerformAssertions();

        try {
            $this->service->upload($file, sys_get_temp_dir());
        } catch (\InvalidArgumentException $e) {
            $this->fail('Le PDF valide ne devrait pas lever InvalidArgumentException : ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            // RuntimeException de move() est attendue en test (pas de vrai répertoire cible)
            // Ce n'est pas une erreur de validation
        }
    }

    /**
     * @testdox Un fichier JPEG valide doit être accepté sans exception de validation
     */
    public function testUploadAcceptsValidJpegExtension(): void
    {
        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize', 'move'])
            ->getMock();

        $file->method('guessExtension')->willReturn('jpg');
        $file->method('getClientOriginalExtension')->willReturn('jpg');
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getSize')->willReturn(2048);
        $file->method('move')->willReturnSelf();

        try {
            $this->service->upload($file, sys_get_temp_dir());
        } catch (\InvalidArgumentException $e) {
            $this->fail('Le JPEG valide ne devrait pas lever InvalidArgumentException : ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            // move() peut échouer en test, c'est normal
        }

        $this->addToAssertionCount(1); // Aucune InvalidArgumentException = test réussi
    }

    /**
     * @testdox Un fichier PNG valide doit être accepté sans exception de validation
     */
    public function testUploadAcceptsValidPngExtension(): void
    {
        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize', 'move'])
            ->getMock();

        $file->method('guessExtension')->willReturn('png');
        $file->method('getClientOriginalExtension')->willReturn('png');
        $file->method('getMimeType')->willReturn('image/png');
        $file->method('getSize')->willReturn(512);
        $file->method('move')->willReturnSelf();

        try {
            $this->service->upload($file, sys_get_temp_dir());
        } catch (\InvalidArgumentException $e) {
            $this->fail('Le PNG valide ne devrait pas lever InvalidArgumentException : ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            // move() peut échouer en test, c'est normal
        }

        $this->addToAssertionCount(1); // Aucune InvalidArgumentException = test réussi
    }

    // -------------------------------------------------------------------------
    // Tests — extensions interdites
    // -------------------------------------------------------------------------

    /**
     * @testdox Un fichier .exe doit être rejeté avec InvalidArgumentException
     */
    public function testUploadRejectsExeExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Format non autorisé/');

        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize'])
            ->getMock();

        $file->method('guessExtension')->willReturn('exe');
        $file->method('getClientOriginalExtension')->willReturn('exe');
        $file->method('getMimeType')->willReturn('application/octet-stream');
        $file->method('getSize')->willReturn(1024);

        $this->service->upload($file, sys_get_temp_dir());
    }

    /**
     * @testdox Un fichier .php doit être rejeté avec InvalidArgumentException
     */
    public function testUploadRejectsPhpExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Format non autorisé/');

        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize'])
            ->getMock();

        $file->method('guessExtension')->willReturn('php');
        $file->method('getClientOriginalExtension')->willReturn('php');
        $file->method('getMimeType')->willReturn('application/x-php');
        $file->method('getSize')->willReturn(256);

        $this->service->upload($file, sys_get_temp_dir());
    }

    /**
     * @testdox Un fichier .html doit être rejeté avec InvalidArgumentException
     */
    public function testUploadRejectsHtmlExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize'])
            ->getMock();

        $file->method('guessExtension')->willReturn('html');
        $file->method('getClientOriginalExtension')->willReturn('html');
        $file->method('getMimeType')->willReturn('text/html');
        $file->method('getSize')->willReturn(128);

        $this->service->upload($file, sys_get_temp_dir());
    }

    // -------------------------------------------------------------------------
    // Tests — taille maximale
    // -------------------------------------------------------------------------

    /**
     * @testdox Un fichier de 6MB doit être rejeté (limite = 5MB)
     */
    public function testUploadRejectsFileLargerThan5MB(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/trop volumineux/');

        $sixMB = 6 * 1024 * 1024;

        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize'])
            ->getMock();

        $file->method('guessExtension')->willReturn('pdf');
        $file->method('getClientOriginalExtension')->willReturn('pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn($sixMB);

        $this->service->upload($file, sys_get_temp_dir());
    }

    /**
     * @testdox Un fichier exactement à 5MB (limite) doit passer la validation de taille
     */
    public function testUploadAcceptsFilesExactlyAt5MB(): void
    {
        $exactlyFiveMB = 5 * 1024 * 1024;

        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize', 'move'])
            ->getMock();

        $file->method('guessExtension')->willReturn('pdf');
        $file->method('getClientOriginalExtension')->willReturn('pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn($exactlyFiveMB);
        $file->method('move')->willReturnSelf();

        try {
            $this->service->upload($file, sys_get_temp_dir());
        } catch (\InvalidArgumentException $e) {
            $this->fail('Un fichier de 5MB exactement ne devrait pas être rejeté : ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            // move() peut échouer, c'est normal en environnement test
        }

        $this->addToAssertionCount(1); // Aucune InvalidArgumentException = test réussi
    }

    // -------------------------------------------------------------------------
    // Tests — MIME type invalide
    // -------------------------------------------------------------------------

    /**
     * @testdox Un fichier avec MIME type application/zip doit être rejeté
     */
    public function testUploadRejectsInvalidMimeType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Type de fichier non valide/');

        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize'])
            ->getMock();

        // Extension "pdf" valide mais contenu ZIP (fichier déguisé)
        $file->method('guessExtension')->willReturn('pdf');
        $file->method('getClientOriginalExtension')->willReturn('pdf');
        $file->method('getMimeType')->willReturn('application/zip');
        $file->method('getSize')->willReturn(1024);

        $this->service->upload($file, sys_get_temp_dir());
    }

    /**
     * @testdox Un fichier avec MIME type text/javascript doit être rejeté
     */
    public function testUploadRejectsJavascriptMimeType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guessExtension', 'getClientOriginalExtension', 'getMimeType', 'getSize'])
            ->getMock();

        $file->method('guessExtension')->willReturn('js');
        $file->method('getClientOriginalExtension')->willReturn('js');
        $file->method('getMimeType')->willReturn('text/javascript');
        $file->method('getSize')->willReturn(512);

        $this->service->upload($file, sys_get_temp_dir());
    }
}
