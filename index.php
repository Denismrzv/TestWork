<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

class ZendeskTickets
{
    private Client $client;
    private string $subdomain;
    private string $token;
    private string $userEmail;

    public function __construct( string $subdomain, string $token, string $userEmail )
    {
        $this->subdomain = $subdomain;
        $this->userEmail = $userEmail;
        $this->token = $token;
        $this->client = new Client([
            'base_uri' => "https://{$this->subdomain}.zendesk.com/api/v2/",
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$this->userEmail}/token:{$this->token}"),
                'Content-Type' => 'application/json',
            ]
        ]);
    }


    public function getTickets()
    {
        Echo("https://{$this->subdomain}.zendesk.com/api/v2/tickets.json");
        $tickets = [];
        $url = 'tickets.json';

        do{
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);
            $tickets = array_merge($tickets, $data['tickets']);
            $url = $data['next_page'] ?? null;
        }while($url);
        return $tickets;
    }


    private function getComments($ticketId)
    {
        $url = "tickets/{$ticketId}/comments.json";
        $response = $this->client->get($url);
        $data = json_decode($response->getBody()->getContents(), true);

        return implode("|", array_column($data['comments'], 'body'));
    }

    private function getUser(int $userID)
    {
        $url = "users/{$userID}.json";
        $response = $this->client->get($url);
        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'name' => $data['user']['name'] ?? '',
            'email' => $data['user']['email'] ?? ''
        ];
    }

    private function getGroupName($groupId): string
    {
        $url = "groups/{$groupId}.json";
        $response = $this->client->get($url);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data['group']['name'] ?? '';
    }

    private function getCompanyName($companyId): string
    {
        $url = "organizations/{$companyId}.json";
        $response = $this->client->get($url);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data['organization']['name'] ?? '';
    }

    public function exportToCSV()
    {
        $filePath = __DIR__ . '/result/tickets_' . date('Y-m-d') . '.csv';


        $csvFile = fopen($filePath, 'w');
        if (!$csvFile) {
            die("Ошибка: Не удалось создать файл {$filePath}");
        }

        fputcsv($csvFile, [
            'Ticket ID', 'Description', 'Status', 'Priority',
            'Agent ID', 'Agent Name', 'Agent Email',
            'Contact ID', 'Contact Name', 'Contact Email',
            'Group ID', 'Group Name', 'Company ID', 'Company Name', 'Comments'
        ]);


        $tickets = $this->getTickets();

        foreach ($tickets as $ticket) {
            $comments = $this->getComments($ticket['id'] ?? 0);
            $agent = isset($ticket['assignee_id']) ? $this->getUser($ticket['assignee_id']) : ['name' => '', 'email' => ''];
            $contact = isset($ticket['requester_id']) ? $this->getUser($ticket['requester_id']) : ['name' => '', 'email' => ''];
            $groupName = isset($ticket['group_id']) ? $this->getGroupName($ticket['group_id']) : '';
            $companyName = isset($ticket['organization_id']) ? $this->getCompanyName($ticket['organization_id']) : '';

            fputcsv($csvFile, [
                $ticket['id'] ?? '',
                substr($ticket['description'],0,11 ) ?? '',
                $ticket['status'] ?? '',
                $ticket['priority'] ?? '',
                $ticket['assignee_id'] ?? '',
                $agent['name'],
                $agent['email'],
                $ticket['requester_id'] ?? '',
                $contact['name'],
                $contact['email'],
                $ticket['group_id'] ?? '',
                $groupName,
                $ticket['organization_id'] ?? '',
                $companyName,
                substr($comments,0,11 ) ?? ''
            ]);
        }

        fclose($csvFile);
        echo "Файл сохранен: {$filePath}\n";
    }

}
$zendesk = new ZendeskTickets('relokia7488', 'HH4T3xQh9u45WR6ZuBPjDdSoSqdLpq48eQcXvM3g', 'den123morozov02@gmail.com');
$zendesk->exportToCSV();