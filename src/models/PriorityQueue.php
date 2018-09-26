<?php
class PriorityQueue {
    const JOB_QUEUE_NAME = "jobqueue";

    public function __construct() {
        $this->client = new Predis\Client();
    }

    /*
     * Accepts a job, sets timestamp and attributes in hash
     * Queues jobid in sorted list
    */
    public function queueJob(Job $job) {
        $jobKey = $job->getKey();

        // add job details to hash for easy lookup of submission time
        $this->client->hset($jobKey, "submitted", time());
        $this->client->hset($jobKey, "submitterId", $job->getSubmitterId());
        $this->client->hset($jobKey, "command", $job->getCommand());
        $this->client->hset($jobKey, "priority", $job->getPriority());
    
        // add jobkey to sorted set, which acts as the job queue
        $this->client->zincrby("jobqueue", 1, $jobKey);
    }

    public function getNextJob() {
        return $this->client->zrevrange(self::JOB_QUEUE_NAME, 0, 0)[0];
    }

    public function dequeueJob() {
        // get next job from front of the queue
        $jobKey = $this->getNextJob();

        // don't attempt to dequeue or update any stats if there are no jobs left
        if ($jobKey) {
            // remove job from queue
            $this->client->zrem(self::JOB_QUEUE_NAME, $jobKey);

            // update time job was processed in the hash
            $this->client->hset($jobKey, "processed", time());

            $jobDetails = $this->client->hgetall($jobKey);

            $this->client->incr("num_jobs_processed");
            $this->client->incrby("total_processing_time", $jobDetails['processed'] - $jobDetails['submitted']);    
        }

        return $jobKey;
    }

    public function getJobStatus($jobKey) {
        $status = $this->client->hgetall($jobKey);
        $status['rank'] = $this->client->zrevrank(self::JOB_QUEUE_NAME, $jobKey);

        return $status;
    }

    public function getStats() {
        $totalProcessingTime = (int) $this->client->get("total_processing_time");
        $numJobsProcessed = (int) $this->client->get("num_jobs_processed");
        
        $averageProcessingTime = $numJobsProcessed > 0  ? ($totalProcessingTime / $numJobsProcessed) : null;

        return [
            "total_processing_time" => $totalProcessingTime,
            "num_jobs_processed" => $numJobsProcessed,
            "average_processing_time" => $averageProcessingTime
        ];
    }

    /*
    * Using for testing, will clear all jobs in the queue!
    */
    public function emptyQueue() {
        $this->client->zremrangebyscore(self::JOB_QUEUE_NAME, "-inf", "inf");
        $this->client->del("total_processing_time");
        $this->client->del("num_jobs_processed");
    }
}