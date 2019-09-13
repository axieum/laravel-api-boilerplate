<?php

namespace Tests\Unit\bootstrap;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    /** @test */
    public function can_get_per_page_between_bounds()
    {
        // Within bounds
        self::assertEquals(27, per_page(27, 15, 100));

        // Out of upper bounds
        self::assertEquals(100, per_page(105, 15, 100));

        // Out of lower bounds
        self::assertEquals(15, per_page(7, 15, 100));
    }

    /** @test */
    public function can_get_per_page_via_query()
    {
        self::get('/?perPage=21');
        self::assertEquals(21, per_page());
    }
}
