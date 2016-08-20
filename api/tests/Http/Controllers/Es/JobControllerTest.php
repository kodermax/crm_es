<?php
/**
 * Created by PhpStorm.
 * User: MKarpychev
 * Date: 10.12.2015
 * Time: 10:36
 */


class JobControllerTest extends TestCase
{
    protected $questionMock;
    public function setUp()
    {
        //$this->questionMock = Mockery::mock('Question');
    }

    /**
     * Test method insert
     */
    public function testInsert()
    {
      /* ob_start();
         $this->expectsJobs(App\Jobs\Es\Job::class);
          $this->post('/es/jobs', [
             'entity_id' => 1038774,
             'entity_type' => 'LEAD',
             'job_type' => 'INSERT'
         ])->assertResponseStatus(201);
       ob_end_clean();*/
    }
}
