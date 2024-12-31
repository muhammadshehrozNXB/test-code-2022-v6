<?php

namespace Tests\Unit;


class WillExpireAtTest extends TestCase
{
    public function testWillExpireWithin90Hours()
    {
        $due_time = Carbon::now()->addHours(80);
        $created_at = Carbon::now();

        $expected = $due_time->format('Y-m-d H:i:s');
        $this->assertEquals($expected, TeHelper::willExpireAt($due_time, $created_at));
    }

    public function testWillExpireWithin24Hours()
    {
        $due_time = Carbon::now()->addHours(10);
        $created_at = Carbon::now();

        $expected = $created_at->addMinutes(90)->format('Y-m-d H:i:s');
        $this->assertEquals($expected, TeHelper::willExpireAt($due_time, $created_at));
    }

    public function testWillExpireWithin72Hours()
    {
        $due_time = Carbon::now()->addHours(50);
        $created_at = Carbon::now();

        $expected = $created_at->addHours(16)->format('Y-m-d H:i:s');
        $this->assertEquals($expected, TeHelper::willExpireAt($due_time, $created_at));
    }

    public function testWillExpireAbove72Hours()
    {
        $due_time = Carbon::parse('2024-01-05 18:00:00');
        $created_at = Carbon::parse('2024-01-02 16:00:00');
        $result = TeHelper::willExpireAt($due_time, $created_at);

        $this->assertEquals('2024-01-03 18:00:00', $result);
    }
}