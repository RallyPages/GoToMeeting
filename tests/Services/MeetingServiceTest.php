<?php
/**
 * Service test class for the meetings.
 */
namespace kenobi883\GoToMeeting\Services;

use Carbon\Carbon;
use kenobi883\GoToMeeting\Models\Meeting;

class MeetingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider singleMeetingProvider
     */
    public function testGetMeeting($responseArray, $expectedMeeting)
    {
        $client = $this->getMockBuilder('Client')
            ->setMethods([
                'sendRequest',
            ])
            ->getMock();
        $client->method('sendRequest')
            ->will($this->returnValue([
                $responseArray,
            ]));
        $meetingService = new MeetingService($client);
        $actualMeeting = $meetingService->getMeeting($responseArray['meetingId']);
        $this->assertNotNull($actualMeeting);
        $this->assertInstanceOf('\kenobi883\GoToMeeting\Models\Meeting', $actualMeeting);
        $this->assertEquals($actualMeeting, $expectedMeeting);
    }

    /**
     * @dataProvider singleMeetingProvider
     */
    public function testGetScheduledMeetings($responseArray, $expectedMeeting)
    {
        $client = $this->getMockBuilder('Client')
            ->setMethods([
                'sendRequest',
            ])
            ->getMock();
        $client->method('sendRequest')
            ->will($this->returnValue([
                    $responseArray,
            ]));
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->stringContains('GET', false),
                $this->stringContains('meetings'),
                $this->attributeEqualTo('data', [
                    'scheduled' => 'true',
                ]));
        $meetingService = new MeetingService($client);
        $meetings = $meetingService->getScheduledMeetings();
        $this->assertNotEmpty($meetings);
        $actualMeeting = $meetings[0];
        $this->assertNotNull($actualMeeting);
        $this->assertInstanceOf('\kenobi883\GoToMeeting\Models\Meeting', $actualMeeting);
        $this->assertEquals($expectedMeeting, $actualMeeting);
    }

    /**
     * @dataProvider singleMeetingProvider
     */
    public function testGetHistoricalMeetings($responseArray, $expectedMeeting)
    {
        $startDate = new \DateTime($responseArray['startTime']);
        $endDate = new \DateTime($responseArray['startTime']);
        $endDate->add(new \DateInterval('P1D'));
        $client = $this->getMockBuilder('Client')
            ->setMethods([
                'sendRequest',
            ])
            ->getMock();
        $client->method('sendRequest')
            ->will($this->returnValue([
                $responseArray,
            ]));
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->stringContains('GET', false),
                $this->stringContains('meetings'),
                $this->attributeEqualTo('data', [
                    'history'   => 'true',
                    'startDate' => $startDate->format(MeetingService::DATE_FORMAT_INPUT),
                    'endDate'   => $endDate->format(MeetingService::DATE_FORMAT_INPUT),
                ]));
        $meetingService = new MeetingService($client);
        $meetings = $meetingService->getHistoricalMeetings($startDate, $endDate);
        $this->assertNotEmpty($meetings);
        $actualMeeting = $meetings[0];
        $this->assertNotNull($actualMeeting);
        $this->assertInstanceOf('\kenobi883\GoToMeeting\Models\Meeting', $actualMeeting);
        $this->assertEquals($expectedMeeting, $actualMeeting);
    }

    /**
     * @dataProvider createMeetingProvider
     */
    public function testCreateMeeting($meeting, $responseArray)
    {
        $client = $this->getMockBuilder('Client')
            ->setMethods([
                'sendRequest',
            ])
            ->getMock();
        $client->method('sendRequest')
            ->will($this->returnValue($responseArray));
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo('POST'));
        $meetingService = new MeetingService($client);
        $actualMeeting = $meetingService->createMeeting($meeting);
        $this->assertNotNull($actualMeeting);
        $this->assertInstanceOf('\kenobi883\GoToMeeting\Models\Meeting', $actualMeeting);
        $this->assertObjectHasAttribute('joinUrl', $actualMeeting);
    }

    public function testDeleteMeeting()
    {
        $meetingId = 123456;
        $client = $this->getMockBuilder('Client')
            ->setMethods([
                'sendRequest',
            ])
            ->getMock();
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo('DELETE'));
        $meetingService = new MeetingService($client);
        $meetingService->deleteMeeting($meetingId);
    }

    /**
     * @dataProvider updateMeetingProvider
     */
    public function testUpdateMeeting($meeting)
    {
        $client = $this->getMockBuilder('Client')
            ->setMethods([
                'sendRequest',
            ])
            ->getMock();
        $client->method('sendRequest');
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo('PUT'));
        $meetingService = new MeetingService($client);
        $meetingService->updateMeeting($meeting);
    }

    public function testStartMeeting()
    {
        $meetingId = 123456789;
        $responseArray = [
            'hostURL' => 'https://downloadstage.citrixonline.com/download.html?startMode=Join&meetingId=123456789&authenticationToken=3A2000000000751489513AtCz8cVV4cqDTvQzRuGe4zQT5&runMode=Normal&displayMode=Join&locale=en_US&buildNumber=977&egwHostname=egwstage.gotomeeting.com&egwPort=80&egwPort=443&egwPort=8200&egwIp=216.219.121.194%2C216.219.121.224&productName=g2m&productType=g2m&theme=g2m',
        ];
        $client = $this->getMockBuilder('Client')
            ->setMethods([
                'sendRequest',
            ])
            ->getMock();
        $client->method('sendRequest')
            ->will($this->returnValue($responseArray));
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo('GET'),
                $this->stringEndsWith('start')
            );
        $meetingService = new MeetingService($client);
        $hostURL = $meetingService->startMeeting($meetingId);
        $this->assertEquals($responseArray['hostURL'], $hostURL);
    }

    /**
     * @dataProvider attendeesByMeetingProvider
     */
    public function testGetAttendeesByMeeting($meetingInstanceKey, \DateTime $startDate, \DateTime $endDate, $responseArray)
    {
        $client = $this->getMockBuilder('Client')
            ->setMethods([
                'sendRequest',
            ])
            ->getMock();
        $client->method('sendRequest')
            ->will($this->returnValue([
                $responseArray,
            ]));
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->stringContains('GET', false),
                $this->stringContains("meetings/{$meetingInstanceKey}/attendees"),
                $this->attributeEqualTo('data', [
                    'startDate' => $startDate->format(MeetingService::DATE_FORMAT_INPUT),
                    'endDate'   => $endDate->format(MeetingService::DATE_FORMAT_INPUT),
                ]));
        $groupService = new MeetingService($client);
        $actualResponse = $groupService->getAttendeesByMeeting($meetingInstanceKey, $startDate, $endDate);
        $this->assertArrayHasKey('meetings', $actualResponse);
        $this->assertArrayHasKey('attendees', $actualResponse);
        $this->assertNotEmpty($actualResponse['meetings']);
        $this->assertInstanceOf('\kenobi883\GoToMeeting\Models\Meeting', $actualResponse['meetings'][0]);
        $this->assertNotEmpty($actualResponse['attendees']);
        $this->assertInstanceOf('\kenobi883\GoToMeeting\Models\Attendee', $actualResponse['attendees'][0]);
    }

    public function singleMeetingProvider()
    {
        $responseArray = [
            'uniqueMeetingId'    => 1230000000456789,
            'meetingId'          => 123456789,
            'createTime'         => '2012-06-25T22:10:46.+0000',
            'status'             => 'INACTIVE',
            'subject'            => 'test',
            'startTime'          => '2012-12-01T09:00:00.+0000',
            'endTime'            => '2012-12-01T10:00:00.+0000',
            'conferenceCallInfo' => 'Australia: +61 2 9037 1944\nCanada: +1 (647) 977-5956\nUnited Kingdom: +44 (0) 207 151 1850\nIreland: +353 (0) 15 290 180\nUnited States: +1 (773) 945-1031\nAccess Code: 111-952-374',
            'passwordRequired'   => 'false',
            'meetingType'        => 'scheduled',
            'maxParticipants'    => 25,
        ];
        $expectedMeeting = new Meeting($responseArray);

        return [
            [
                $responseArray,
                $expectedMeeting,
            ],
        ];
    }

    public function createMeetingProvider()
    {
        $meeting = new Meeting();
        $meeting->setSubject('test');
        $meeting->setStartTime(Carbon::now('UTC'));
        $meeting->setEndTime(Carbon::now('UTC')->addHour());
        $meeting->setPasswordRequired(false);
        $meeting->setConferenceCallInfo(Meeting::CONFERENCE_CALL_HYBRID);
        $meeting->setMeetingType(Meeting::TYPE_IMMEDIATE);
        $responseArray = [
            [
                'joinURL'            => 'https://www3.gotomeeting.com/join/762836476',
                'maxParticipants'    => 26,
                'uniqueMeetingId'    => 200000000212521696,
                'conferenceCallInfo' => 'Australia: +61 2 8355 0000\nCanada: +1 (416) 900-1111\nUnited Kingdom: +44 (0) 203 535 0000\nIreland: +353 (0) 14 000 976\nUnited States: +1 (786) 358-0000\nAccess Code: 762-836-476',
                'meetingid'          => 762836476,
            ],
        ];

        return [
            [
                $meeting,
                $responseArray,
            ],
        ];
    }

    public function updateMeetingProvider()
    {
        $meeting = new Meeting();
        $meeting->setSubject('test');
        $meeting->setStartTime(Carbon::now('UTC'));
        $meeting->setEndTime(Carbon::now('UTC')->addHour());
        $meeting->setPasswordRequired(false);
        $meeting->setConferenceCallInfo(Meeting::CONFERENCE_CALL_HYBRID);
        $meeting->setMeetingType(Meeting::TYPE_IMMEDIATE);

        return [
            [
                $meeting,
            ],
        ];
    }

    public function attendeesByMeetingProvider()
    {
        $meetingInstanceKey = 12345;
        $startDate = new \DateTime();
        $endDate = new \DateTime();
        $endDate->add(new \DateInterval('P1W'));
        $responseArray = [
            [
                'organizerKey'       => 123456789,
                'firstName'          => 'John',
                'lastName'           => 'Smith',
                'email'              => 'johnsmith@example.com',
                'meetingId'          => 123456789,
                'meetingInstanceKey' => 1,
                'subject'            => 'test',
                'startTime'          => '2012-12-01T09:00:00.+0000',
                'endTime'            => '2012-12-01T10:00:00.+0000',
                'duration'           => 60,
                'conferenceCallInfo' => 'Australia: +61 2 9037 1944\nCanada: +1 (647) 977-5956\nUnited Kingdom: +44 (0) 207 151 1850\nIreland: +353 (0) 15 290 180\nUnited States: +1 (773) 945-1031\nAccess Code: 111-952-374',
                'meetingType'        => 'scheduled',
                'numAttendees'       => 1,
                'groupName'          => 'Developers',
            ],
        ];

        return [
            [
                $meetingInstanceKey,
                $startDate,
                $endDate,
                $responseArray,
            ],
        ];
    }
}
