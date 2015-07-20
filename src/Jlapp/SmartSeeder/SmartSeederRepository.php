<?php namespace Jlapp\SmartSeeder;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;

class SmartSeederRepository implements MigrationRepositoryInterface
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * The name of the environment to run in.
     *
     * @var string
     */
    protected $env;

    /**
     * Create a new database migration repository instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param string                                           $table
     * @param string                                           $environment
     */
    public function __construct(Resolver $resolver, $table, $environment)
    {
        $this->table    = $table;
        $this->resolver = $resolver;
        $this->env      = $environment;
    }

    /**
     * Get the environment we run in.
     *
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Set the environment to run the seeds against.
     *
     * @param $env
     */
    public function setEnv($env)
    {
        $this->env = $env;
    }

    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan()
    {
        return $this->table()->where('env', '=', $this->env)->lists('seed');
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        $query = $this->table()->where('env', '=', $this->env)->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('seed', 'desc')->get();
    }

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param int    $batch
     */
    public function log($file, $batch)
    {
        $record = array('seed' => $file, 'env' => $this->env, 'batch' => $batch);

        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * @param $seed
     *
     * @internal param object $migration
     */
    public function delete($seed)
    {
        $this->table()->where('env', '=', $this->env)->where('seed', $seed->seed)->delete();
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()->where('env', '=', $this->env)->max('batch');
    }

    /**
     * Create the migration repository data store.
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function (Blueprint $table) {
            // The seeds table is responsible for keeping track of which of the
            // seeds have actually run for the application. We'll create the
            // table to hold the seed file's path as well as the batch ID.
            $table->string('seed');
            $table->string('env');
            $table->integer('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table);
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the information source to gather data.
     *
     * @param string $name
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}
