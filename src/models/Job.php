<?php
class Job {
    private $id; 
    private $submitterId;
    private $priority;
    private $command;

    public function __construct() {
        $this->id = uniqid();
    }

    public function getId() {
        return $this->id;
    }

    public function getKey() {
        return sprintf("job:%s", $this->id);
    }

    public function getPriority() {
        return $this->priority;
    }

    public function setPriority(int $priority) {
        $this->priority = $priority;
    }
    
    public function getSubmitterId() {
        return $this->submitterId;
    }

    public function setSubmitterId(int $submitterId) {
        $this->submitterId = $submitterId;
    }

    public function getCommand() {
        return $this->command;
    }
    
    public function setCommand(string $command) {
        $this->command = $command;
    }   
}