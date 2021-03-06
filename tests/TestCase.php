<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    use DatabaseTransactions;

    private $jwtToken = null;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('RUNNING_TESTS=true');
        putenv('DB_CONNECTION=mysql_test');

        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        Artisan::call('migrate');

        return $app;
    }

    public function setUp()
    {
        $this->jwtToken = null;
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function createFile($type)
    {
        $files = require 'files/files.php';

        if (!isset($files[$type])) {
            throw new Exception('Invalid file type ' . $type . '.');
        }

        return $files[$type]();
    }

    /**
     * Create a new token to use in api call requests
     *
     * @param string $email
     * @return $this
     */
    public function withNewToken(string $email = null)
    {
        $data = $email ? ['email' => $email] : [];
        $user = factory(App\Models\User::class)->create($data);
        $this->jwtToken = JWTAuth::fromUser($user);
        return $this;
    }


    /**
     * Create a token for an existing user and uses it for subsequent requests
     *
     * @param User $user
     * @return $this
     */
    public function withToken(User $user)
    {
        $this->jwtToken = JWTAuth::fromUser($user);
        return $this;
    }

    /**
     * Wrapper for Laravel json test function that also validates the structure of the response.
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param array $files
     * @return $this
     */
    public function apiCall(string $method, string $url, $data = [], $headers = [], $files = [])
    {
        if ($this->jwtToken) {
            $headers['Authorization'] = 'Bearer ' . $this->jwtToken;
        }

        $content = json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        $this->call(
            $method, $url, [], [], $files, $this->transformHeadersToServerVars($headers), $content
        );

        return $this
            ->seeJsonStructure([
                'result' => [
                    'success',
                    'count'
                ],
                'response_payload'
            ]);
    }


    /**
     * Wrapper to check if a response contains an error.
     *
     * @param int $status
     * @return $this
     */
    public function seeError(int $status)
    {
        return $this->seeJson([
            'success' => false
        ])->assertResponseStatus($status);
    }

    /**
     * Wrapper to check if a response was successful.
     *
     * @return $this
     */
    public function seeSuccess()
    {
        return $this->seeJson([
            'success' => true
        ])->assertResponseStatus(200);
    }

    /**
     * Wrapper for seeJsonStructure that checks the payload rather than the whole response.
     *
     * @param array $structure
     * @return $this
     */
    public function seePayloadStructure(array $structure)
    {
        return $this->seeJsonStructure([
            'response_payload' => [
                'data' => $structure
            ]
        ]);
    }

    /**
     * Wrapper for seeJsonStructure that checks the payload rather than the whole response.
     *
     * @param array $structure
     * @return $this
     */
    public function seePaginationStructure(array $structure)
    {
        $data = $this->decodeResponseJson();
        $this->assertGreaterThanOrEqual(1, $data['response_payload']['total'], 'No items in pagination.');

        return $this->seeJsonStructure([
            'response_payload' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
                'from',
                'to',
                'data' => ['*' => $structure]
            ]
        ]);
    }

    /**
     * Method to check order of objects in pagination response.
     *
     * @param array $items
     * @param string $columnName
     * @return $this
     */
    public function seePaginationItemsInOrder(array $items, string $columnName = 'id')
    {
        $data = $this->decodeResponseJson();

        foreach ($items as $i => $item) {
            $this->assertArrayHasKey($i, $data['response_payload']['data'],
                "Ran out of array keys to compare at index " . $i . ".\n" . json_encode($data));
            $this->assertEquals($item->$columnName, $data['response_payload']['data'][$i][$columnName],
                'Wrong order at index ' . $i . '. ' . $item->$columnName . ' does not equal ' .
                $data['response_payload']['data'][$i][$columnName]);
        }

        return $this;
    }

    /**
     * Check items in paginated response.
     *
     * @param integer $count
     * @return $this
     */
    public function seePaginationCount(int $count)
    {
        $data = $this->decodeResponseJson();
        $this->assertEquals($count, $data['response_payload']['total'],
            'Total does not match. Expected ' . $count . ' got ' . $data['response_payload']['total']);

        return $this;
    }
}