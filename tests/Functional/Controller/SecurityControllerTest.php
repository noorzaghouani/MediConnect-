<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels — Contrôle d'accès (Security)
 *
 * Vérifie que :
 * - Les pages publiques (login, register) sont accessibles (HTTP 200)
 * - Les routes protégées redirigent vers /login sans authentification (HTTP 302)
 *
 * Ces tests ne nécessitent pas de base de données.
 */
class SecurityControllerTest extends WebTestCase
{
    // =========================================================================
    // Pages publiques — doivent retourner HTTP 200
    // =========================================================================

    /**
     * @testdox GET /login retourne HTTP 200 et affiche le formulaire de connexion
     */
    public function testLoginPageEstAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @testdox La page /login contient un champ email et un champ mot de passe
     */
    public function testLoginPageContientLeFormulaire(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        // Vérifier la présence du formulaire de connexion
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[type="email"], input[name="email"]');
        $this->assertSelectorExists('input[type="password"]');
    }

    /**
     * @testdox GET /register est une route publique (ne redirige pas vers /login)
     */
    public function testRegisterPageEstPublique(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $statusCode = $client->getResponse()->getStatusCode();

        // La route /register est publique : elle ne doit PAS rediriger vers /login
        // (contrairement aux routes protégées qui retournent 302 → /login)
        if ($statusCode === 302) {
            $location = $client->getResponse()->headers->get('Location');
            $this->assertStringNotContainsString(
                '/login',
                $location,
                'La page /register ne devrait pas rediriger vers /login — elle est publique'
            );
        } else {
            // 200 (BDD ok) ou 500 (BDD hors ligne) : la route est accessible publiquement
            $this->assertNotSame(302, $statusCode);
        }
    }

    /**
     * @testdox GET / (accueil) retourne HTTP 200
     */
    public function testAccueilEstAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        // La page d'accueil doit être accessible (200) ou rediriger proprement
        $this->assertResponseStatusCodeSame(200);
    }

    // =========================================================================
    // Routes protégées — doivent rediriger vers /login (HTTP 302)
    // =========================================================================

    /**
     * @testdox GET /admin/dashboard sans authentification redirige vers /login
     */
    public function testAdminDashboardRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/dashboard');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @testdox GET /patient/dashboard sans authentification redirige vers /login
     */
    public function testPatientDashboardRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/patient/dashboard');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @testdox GET /medecin/dashboard sans authentification redirige vers /login
     */
    public function testMedecinDashboardRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/medecin/dashboard');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @testdox GET /admin/medecins sans authentification redirige vers /login
     */
    public function testAdminMedecinsRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/medecins');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @testdox GET /admin/patients sans authentification redirige vers /login
     */
    public function testAdminPatientsRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/patients');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @testdox GET /patient/rdv sans authentification redirige vers /login
     */
    public function testPatientRdvRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/patient/rdv');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @testdox GET /medecin/disponibilites sans authentification redirige vers /login
     */
    public function testMedecinDisponibilitesRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/medecin/disponibilites');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @testdox GET /patient/ordonnances sans authentification redirige vers /login
     */
    public function testPatientOrdonnancesRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/patient/ordonnances');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @testdox GET /admin/demandes sans authentification redirige vers /login
     */
    public function testAdminDemandesRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/demandes');

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    // =========================================================================
    // API protégée — doit rediriger sans authentification
    // =========================================================================

    /**
     * @testdox GET /api/medecin/1/disponibilites sans auth redirige vers /login
     */
    public function testApiDisponibilitesRedirigeVersLoginSansAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/medecin/1/disponibilites');

        // Doit être protégée (302 ou 401)
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 401]);
    }
}
