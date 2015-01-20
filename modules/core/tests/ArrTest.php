<?php

namespace Klio\Tests;

class ArrTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function longestCommonPrefix()
    {
        $this->assertEquals("abc", \Klio\Arr::lcp("abcd", "abcef"));
        $this->assertEquals("a", \Klio\Arr::lcp("abcd", "accdg"));
    }
}
