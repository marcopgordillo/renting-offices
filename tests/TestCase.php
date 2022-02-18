<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\Constraints\NotSoftDeletedInDatabase;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

     /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = true;

    /**
     * Assert the given record has not been "soft deleted".
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @param  string|null  $deletedAtColumn
     * @return $this
     */
    protected function assertNotSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at')
    {
        if ($this->isSoftDeletableModel($table)) {
            return $this->assertNotSoftDeleted(
                $table->getTable(),
                array_merge($data, [$table->getKeyName() => $table->getKey()]),
                $table->getConnectionName(),
                $table->getDeletedAtColumn()
            );
        }

        $this->assertThat(
            $this->getTable($table), new NotSoftDeletedInDatabase($this->getConnection($connection, $table), $data, $deletedAtColumn)
        );

        return $this;
    }

    /**
     * Set the currently logged in user for the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string|null  $guard
     * @return $this
     */
    public function actingAs(UserContract $user, $abilities = ['*'])
    {
        Sanctum::actingAs($user, $abilities);

        return $this;
    }
}
