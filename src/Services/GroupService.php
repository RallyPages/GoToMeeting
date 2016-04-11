<?php
/**
 * Service to interact with groups endpoint.
 */
namespace kenobi883\GoToMeeting\Services;

use kenobi883\GoToMeeting\Models\Attendee;
use kenobi883\GoToMeeting\Models\Group;
use kenobi883\GoToMeeting\Models\Meeting;
use kenobi883\GoToMeeting\Models\Organizer;

class GroupService extends AbstractService
{
    /**
     * @var string
     */
    protected $endpoint = 'groups';

    /**
     * Retrieve all groups for the corporate account.
     *
     * Requires a corporate account and a user with the admin role.
     *
     * @return array Group objects for the account
     */
    public function getGroups()
    {
        $jsonBody = $this->client->sendRequest('GET', $this->endpoint);
        $groups = [];
        foreach ($jsonBody as $groupResponse) {
            $groups[] = new Group($groupResponse);
        }

        return $groups;
    }

    /**
     * Get the organizers for a particular group.
     *
     * @param int $groupKey
     *
     * @return array Organizers for the account
     */
    public function getOrganizersByGroup($groupKey)
    {
        $jsonBody = $this->client->sendRequest('GET', "{$this->endpoint}/{$groupKey}/organizers");
        $organizers = [];
        foreach ($jsonBody as $organizerResponse) {
            $organizers[] = new Organizer($organizerResponse);
        }

        return $organizers;
    }

    /**
     * Create a new organizer in the specified group.
     *
     * @param int       $groupKey
     * @param Organizer $organizer
     *
     * @return Organizer with organizer key specified
     */
    public function createOrganizer($groupKey, Organizer $organizer)
    {
        $url = "{$this->endpoint}/{$groupKey}/organizers";
        $jsonBody = $this->client->sendRequest('POST', $url, null, false, $organizer->toArrayForApi());
        $organizer->setOrganizerKey($jsonBody);

        return $organizer;
    }

    /**
     * Get historical or scheduled meetings by group.
     *
     * @param string    $groupKey
     * @param bool      $historical
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @throws \Exception
     *
     * @return array Meetings for the given group and optional date range
     */
    public function getMeetingsByGroup($groupKey, $historical = false, \DateTime $startDate = null, \DateTime $endDate = null)
    {
        if ($historical === true && ($startDate === null || $endDate === null)) {
            throw new \Exception('To retrieve historical meetings, startDate and endDate must be specified.');
        }
        $query = [];
        $url = "{$this->endpoint}/{$groupKey}/meetings";

        if ($historical === true) {
            // Adjust start and end dates to the UTC timezone
            $utcTimeZone = new \DateTimeZone('UTC');
            $startDate->setTimezone($utcTimeZone);
            $endDate->setTimezone($utcTimeZone);
            $query['historical'] = 'true';
            $query['startDate'] = $startDate->format(MeetingService::DATE_FORMAT_INPUT);
            $query['endDate'] = $endDate->format(MeetingService::DATE_FORMAT_INPUT);
        } else {
            $query['scheduled'] = 'true';
        }

        // Send request
        $jsonBody = $this->client->sendRequest('GET', $url, $query);

        // Parse each meeting result
        $meetings = [];
        foreach ($jsonBody as $oneMeeting) {
            $meeting = new Meeting($oneMeeting);
            $meetings[] = $meeting;
        }

        return $meetings;
    }

    /**
     * Get attendee information for a given group and date range.
     *
     * @param string    $groupKey
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return array includes `meetings` and `attendees` keys mapping to arrays of the Meeting and Attendee
     *               instances returned from the API
     */
    public function getAttendeesByGroup($groupKey, \DateTime $startDate, \DateTime $endDate)
    {
        $url = "{$this->endpoint}/{$groupKey}/attendees";
        $query = [];
        $query['startDate'] = $startDate->format(MeetingService::DATE_FORMAT_INPUT);
        $query['endDate'] = $endDate->format(MeetingService::DATE_FORMAT_INPUT);

        $jsonBody = $this->client->sendRequest('GET', $url, $query);
        $meetings = [];
        $attendees = [];
        foreach ($jsonBody as $meetingAttendee) {
            $meeting = new Meeting($meetingAttendee);
            $attendee = new Attendee($meetingAttendee);
            $meetings[] = $meeting;
            $attendees[] = $attendee;
        }

        return [
            'meetings'  => $meetings,
            'attendees' => $attendees,
        ];
    }
}
