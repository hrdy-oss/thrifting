<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_no_longer_links_to_the_admin_login(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('login/admin');
    }

    public function test_the_admin_login_lives_at_the_private_login_admin_path(): void
    {
        $this->assertSame('/login/admin', parse_url(route('admin.login'), PHP_URL_PATH));

        $this->get('/login/admin')->assertOk();
    }

    public function test_guests_are_redirected_to_the_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }
}
