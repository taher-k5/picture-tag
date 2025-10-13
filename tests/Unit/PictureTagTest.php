<?php

namespace taherkathiriya\craftpicturetag\tests\unit;

use Codeception\Test\Unit;
use craft\test\mockclasses\components\MockComponent;
use taherkathiriya\craftpicturetag\PictureTag;
use taherkathiriya\craftpicturetag\models\Settings;

/**
 * Unit tests for PictureTag plugin
 */
class PictureTagTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Test plugin initialization
     */
    public function testPluginInitialization(): void
    {
        $plugin = PictureTag::getInstance();
        $this->assertInstanceOf(PictureTag::class, $plugin);
    }

    /**
     * Test settings model creation
     */
    public function testSettingsModel(): void
    {
        $settings = new Settings();
        $this->assertInstanceOf(Settings::class, $settings);
        
        // Test default values
        $this->assertTrue($settings->enableWebP);
        $this->assertFalse($settings->enableAvif);
        $this->assertTrue($settings->enableLazyLoading);
        $this->assertEquals(80, $settings->webpQuality);
    }

    /**
     * Test settings validation
     */
    public function testSettingsValidation(): void
    {
        $settings = new Settings();
        
        // Test valid settings
        $settings->webpQuality = 85;
        $settings->enableWebP = true;
        $this->assertTrue($settings->validate());
        
        // Test invalid quality
        $settings->webpQuality = 150; // Invalid
        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('webpQuality', $settings->getErrors());
    }

    /**
     * Test breakpoint validation
     */
    public function testBreakpointValidation(): void
    {
        $settings = new Settings();
        
        // Test valid breakpoints
        $settings->defaultBreakpoints = [
            'mobile' => 480,
            'tablet' => 768,
            'desktop' => 1024,
        ];
        $this->assertTrue($settings->validate());
        
        // Test invalid breakpoint
        $settings->defaultBreakpoints = [
            'mobile' => -100, // Invalid
        ];
        $this->assertFalse($settings->validate());
    }

    /**
     * Test transform validation
     */
    public function testTransformValidation(): void
    {
        $settings = new Settings();
        
        // Test valid transforms
        $settings->defaultTransforms = [
            'mobile' => ['width' => 480, 'height' => 320, 'quality' => 80],
        ];
        $this->assertTrue($settings->validate());
        
        // Test invalid transform
        $settings->defaultTransforms = [
            'mobile' => ['width' => -100, 'height' => 320, 'quality' => 80],
        ];
        $this->assertFalse($settings->validate());
    }

    /**
     * Test getBreakpointForWidth method
     */
    public function testGetBreakpointForWidth(): void
    {
        $settings = new Settings();
        
        // Test mobile breakpoint
        $this->assertEquals('mobile', $settings->getBreakpointForWidth(400));
        
        // Test tablet breakpoint
        $this->assertEquals('tablet', $settings->getBreakpointForWidth(600));
        
        // Test desktop breakpoint
        $this->assertEquals('desktop', $settings->getBreakpointForWidth(900));
        
        // Test large breakpoint
        $this->assertEquals('large', $settings->getBreakpointForWidth(1300));
    }

    /**
     * Test getTransformForBreakpoint method
     */
    public function testGetTransformForBreakpoint(): void
    {
        $settings = new Settings();
        
        // Test existing transform
        $transform = $settings->getTransformForBreakpoint('mobile');
        $this->assertIsArray($transform);
        $this->assertArrayHasKey('width', $transform);
        $this->assertArrayHasKey('height', $transform);
        $this->assertArrayHasKey('quality', $transform);
        
        // Test non-existing transform
        $transform = $settings->getTransformForBreakpoint('nonexistent');
        $this->assertNull($transform);
    }
}
