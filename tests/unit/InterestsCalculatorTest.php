<?php

class InterestsCalculatorTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
    public function testCalculateInterests()
    {
        $json = json_encode(['tags' => ['laravel' => 1, 'php' => 2]]);

        $calculator = new \Coyote\Services\Skills\Calculator($json);
        $result = $calculator->toArray();

        $this->assertEquals(['php' => 1, 'laravel' => 0.5], $result['ratio']);

        /////////////////////////////////////////////////////////////////////////////////////

        $json = json_encode(['tags' => ["mysql" => 144, "python" => 506, "php" => 1034]]);

        $calculator = new \Coyote\Services\Skills\Calculator($json);
        $result = $calculator->toArray();

        $this->assertEquals(['php' => 1, 'python' => 0.48936170212766, 'mysql' => 0.13926499032882], $result['ratio']);
    }

    public function testCalculateInterestsJson()
    {
        $json = json_encode(['tags' => ["mysql" => 144, "python" => 506, "php" => 1034, "c++" => 333]]);

        $calculator = new \Coyote\Services\Skills\Calculator($json);
        $result = $calculator->toJson();

        $this->assertJson($result);
    }
}
