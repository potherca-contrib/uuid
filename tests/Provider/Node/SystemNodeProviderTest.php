<?php

namespace Ramsey\Uuid\Test\Provider\Node;

use Ramsey\Uuid\Provider\Node\SystemNodeProvider;
use Ramsey\Uuid\Test\TestCase;
use AspectMock\Test as AspectMock;

class SystemNodeProviderTest extends TestCase
{
    /**
     */
    public function testGetNodeReturnsSystemNodeFromMacAddress()
    {
        $provider = new SystemNodeProvider(PHP_EOL . 'AA-BB-CC-DD-EE-FF' . PHP_EOL);

        $node = $provider->getNode();

        $this->assertTrue(ctype_xdigit($node), 'Node should be a hexadecimal string. Actual node: ' . $node);
        $length = strlen($node);
        $lengthError = 'Node should be 12 characters. Actual length: ' . $length . PHP_EOL . ' Actual node: ' . $node;
        $this->assertTrue(($length === 12), $lengthError);
    }

    public function notationalFormatsDataProvider()
    {
        return [
            ['01-23-45-67-89-ab', '0123456789ab'],
            ['01:23:45:67:89:ab', '0123456789ab']
        ];
    }

    /**
     * @dataProvider notationalFormatsDataProvider
     * @param $formatted
     * @param $expected
     */
    public function testGetNodeReturnsNodeStrippedOfNotationalFormatting($formatted, $expected)
    {
        $provider = new SystemNodeProvider(PHP_EOL . $formatted . PHP_EOL);

        $node = $provider->getNode();
        $this->assertEquals($expected, $node);
    }

    /**
     */
    public function testGetNodeReturnsFirstMacAddressFound()
    {
        $provider = new SystemNodeProvider(PHP_EOL . 'AA-BB-CC-DD-EE-FF' . PHP_EOL . '00-11-22-33-44-55' . PHP_EOL . 'FF-11-EE-22-DD-33' . PHP_EOL);

        $node = $provider->getNode();
        $this->assertEquals('AABBCCDDEEFF', $node);
    }

    /**
     */
    public function testGetNodeReturnsFalseWhenNodeIsNotFound()
    {
        $provider = new SystemNodeProvider('some string that does not match the mac address');

        $node = $provider->getNode();
        $this->assertFalse($node);
    }

    /**
     */
    public function testGetNodeWillNotExecuteSystemCallIfFailedFirstTime()
    {
        $provider = new SystemNodeProvider('some string that does not match the mac address');

        $provider->getNode();
        $provider->getNode();
    }

    public function osCommandDataProvider()
    {
        return [
            'windows' => ['Windows', 'ipconfig /all 2>&1'],
            'mac' => ['Darwhat', 'ifconfig 2>&1'],
            'linux' => ['Linux', 'netstat -ie 2>&1'],
            'anything_else' => ['someotherxyz', 'netstat -ie 2>&1']
        ];
    }

    /**
     * @dataProvider osCommandDataProvider
     * @param $os
     * @param $command
     */
    public function testGetNodeGetsNetworkInterfaceConfig($os, $command)
    {
        $this->skipIfHhvm();
        AspectMock::func('Ramsey\Uuid\Provider\Node', 'php_uname', $os);
        $passthru = AspectMock::func('Ramsey\Uuid\Provider\Node', 'passthru', 'whatever');

        $provider = new SystemNodeProvider();
        $provider->getNode();
        $passthru->verifyInvokedOnce([$command]);
    }

    /**
     */
    public function testGetNodeReturnsSameNodeUponSubsequentCalls()
    {
        $provider = new SystemNodeProvider(PHP_EOL . 'AA-BB-CC-DD-EE-FF' . PHP_EOL);

        $node = $provider->getNode();
        $node2 = $provider->getNode();
        $this->assertEquals($node, $node2);
    }

    /**
     */
    public function testSubsequentCallsToGetNodeDoNotRecallIfconfig()
    {
        $provider = new SystemNodeProvider(PHP_EOL . 'AA-BB-CC-DD-EE-FF' . PHP_EOL);

        $provider->getNode();
        $provider->getNode();
    }
}
