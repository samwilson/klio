<?php

namespace Klio\Tests;

/**
 * @group klio.core
 * @group klio.quick
 */
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

    /**
     * @test
     */
    public function prefixGroups()
    {
        $input = array(
            'words_one',
            'words_two',
            'words_three',
        );
        $this->assertEquals(['words_'], \Klio\Arr::getPrefixGroups($input));
    }
}
