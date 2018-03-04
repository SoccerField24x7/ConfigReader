<?php
/**
 * Created by PhpStorm.
 * User: Jesse
 * Date: 3/3/2018
 * Time: 2:15 PM
 */
declare(strict_types=1);

include "../vendor/autoload.php";
include "include/cls_ConfigReader.php";

use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $this->assertInstanceOf(
            ConfigReader::class, new ConfigReader()
        );
    }

    /*public function testCreatedFromInvalidFile(): void
    {
        $reader = new ConfigReader('file.txt');

        $this->assertInstanceOf(
            ConfigReader::class, new ConfigReader('file.txt')
        );
    }

    public function testCanBeUsedAsString(): void
    {
        $this->assertEquals(
            'user@example.com',
            Email::fromString('user@example.com')
        );
    }*/
}