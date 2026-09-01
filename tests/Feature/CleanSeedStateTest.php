<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Church;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanSeedStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_keeps_referentials_but_no_members_or_accounting_entries(): void
    {
        $this->seed(EreveSeeder::class);

        $this->assertSame(0, Member::count());
        $this->assertSame(0, JournalEntry::count());
        $this->assertGreaterThan(0, Church::count());
        $this->assertGreaterThan(0, ChartOfAccount::count());

        $admin = User::where('email', 'admin@ereve.cd')->firstOrFail();
        $this->assertNull($admin->member_id);
        $this->assertSame('coordination', $admin->level);
        $this->assertSame('actif', $admin->status);
    }
}
