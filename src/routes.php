<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Classes to represent job and priority queue
require_once('models/Job.php');
require_once('models/PriorityQueue.php');

// Routes

/*
 * Adds a new job to the queue, assumes a valid command, priority, and submitter id are sent
 * Higher number means higher priority in the queue
 * Returns a key to the submitter that can be used to check the status of the job
 */
$app->post('/job', function (Request $request, Response $response) {
    $postVars = $request->getParsedBody();
    
    // create a new job using the posted values
    $job = new Job();
    $job->setCommand($postVars['command']);
    $job->setPriority($postVars['priority']);
    $job->setSubmitterId($postVars['submitter_id']);

    // queue the job
    $queue = new PriorityQueue();
    $queue->queueJob($job);

    $this->logger->info(sprintf("Queueing a new job, assigned a unique id of %s", $job->getKey()));
    
    return $response->withJson(["id" => $job->getKey()]);
});

/* 
 * Get the next available job with the highest priority
 */
$app->get('/job', function (Request $request, Response $response, array $args) {
    $this->logger->info("Received request for the job with highest priority");

    $queue = new PriorityQueue();
    $jobId = $queue->getNextJob();

    return $response->withJson(["jobId" => $jobId]);
});

 /** 
 * Get the status of the job with id = $id, return status as json
 */
$app->get('/job/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->info(sprintf("getting the status of job %s", $args['id']));

    $queue = new PriorityQueue();
    $status = $queue->getJobStatus($args['id']);

    return $response->withJson($status);
});

/**
 * Processes the next job in the queue, removes it from the queue
 * Marks with timestamp when processed
 */
$app->put('/job', function (Request $request, Response $response, array $args) {
    $this->logger->info("Received request to process the next job in the queue");

    $queue = new PriorityQueue();
    $jobId = $queue->dequeueJob();

    return $response->withJson(["jobId" => $jobId]);
});

/**
 * Get queue stats, including average processing time
 */
$app->get('/stats', function (Request $request, Response $response) {
    $this->logger->info("Received request to get queue stats");

    $queue = new PriorityQueue();
    $stats = $queue->getStats();

    return $response->withJson($stats);
});

/*
 * Clear the queue and reset stats
 */
$app->get('/clearjobs', function (Request $request, Response $response) {
    $queue = new PriorityQueue();
    $queue->emptyQueue();
    
    return "queue cleared";
});
