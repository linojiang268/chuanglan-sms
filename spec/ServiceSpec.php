<?php
namespace spec\Ouarea\Sms\Chuanglan;

use GuzzleHttp\RequestOptions;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

class ServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $client)
    {
        $this->beAnInstanceOf(\Ouarea\Sms\Chuanglan\Service::class, [
            'account',    // account
            'password',   // password
            [
                'affix' => '123',  // affix
            ],
            $client
        ]);
    }

    //=====================================
    //          Send Message
    //=====================================
    function it_throws_exception_if_message_too_long(ClientInterface $client)
    {
        $this->shouldThrow(new \Exception('短信内容过长'))
            ->duringSend(str_random(1000), '13800138000');
    }

    function it_throws_exception_if_no_subscribers_given(ClientInterface $client)
    {
        $this->shouldThrow(new \Exception('短信接收用户未指定'))
            ->duringSend(str_random(), '');
    }

    function it_should_have_message_sent_for_single_subscriber(ClientInterface $client)
    {
        $client->request('POST', 'https://sms.253.com/msg/send',
            Argument::that(function (array $request) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['un']    == 'account' &&
                $request['pw']  == 'password' &&
                $request['phone'] == '13800138000' &&
                $request['msg'] == '发送消息' &&
                $request['rd'] == 0 &&
                $request['ex'] == '123';
            }))->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/data/single_success.txt')));

        $this->shouldNotThrow()->duringSend('发送消息', '13800138000');
    }

    function it_should_have_message_sent_for_multiple_subscribers(ClientInterface $client)
    {
        $client->request('POST', 'https://sms.253.com/msg/send',
            Argument::that(function (array $request) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['un']    == 'account' &&
                $request['pw']  == 'password' &&
                $request['phone'] == '13800138000,13800138001' &&
                $request['msg'] == '发送消息' &&
                $request['rd'] == false &&
                $request['ex'] == '123';
            }))->shouldBeCalled()->willReturn(new Response(200, [],
            file_get_contents(__DIR__ . '/data/multi_success.txt')));

        $this->shouldNotThrow()->duringSend('发送消息', ['13800138000', '13800138001']);
    }

    function it_should_have_message_sent_for_subscribers_that_exceeds_the_limit(ClientInterface $client)
    {
        $subscribersBatchOne = $this->makeSubscribers('13800',   1, 200); // 200 subscribers
        $subscribersBatchTwo = $this->makeSubscribers('13800', 201, 300); // 100 subscribers

        $client->request('POST', 'https://sms.253.com/msg/send',
            Argument::that(function (array $request) use ($subscribersBatchOne) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['un']    == 'account' &&
                $request['pw']  == 'password' &&
                $request['phone'] == implode(',', $subscribersBatchOne) &&
                $request['msg'] == 'message' &&
                $request['rd'] == false &&
                $request['ex'] == '123';
            }))->shouldBeCalled()->willReturn(new Response(200, [],
            file_get_contents(__DIR__ . '/data/multi_success.txt')));
        $client->request('POST', 'https://sms.253.com/msg/send',
            Argument::that(function (array $request) use ($subscribersBatchTwo) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['un']    == 'account' &&
                $request['pw']  == 'password' &&
                $request['phone'] == implode(',', $subscribersBatchTwo) &&
                $request['msg'] == 'message' &&
                $request['rd'] == false &&
                $request['ex'] == '123';
            }))->shouldBeCalled()->willReturn(new Response(200, [],
            file_get_contents(__DIR__ . '/data/multi_success.txt')));

        $this->shouldNotThrow()->duringSend('message', array_merge($subscribersBatchOne, $subscribersBatchTwo));
    }

    private function makeSubscribers($prefix, $from, $to)
    {
        $pad = 11 - strlen($prefix);  // each mobile number takes 11 in length

        $subscribers = [];
        for ($i = $from; $i <= $to; $i++) {
            $subscribers[] = $prefix . str_pad($i, $pad, STR_PAD_LEFT);
        }

        return $subscribers;
    }

    //=====================================
    //          Query Quota
    //=====================================
    function it_should_have_quota_queried(ClientInterface $client)
    {
        $client->request('POST', 'https://sms.253.com/msg/balance',
            Argument::that(function (array $request) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['un'] == 'account' &&
                $request['pw'] == 'password';
            }))->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/data/surplus.txt')));

        $this->queryQuota()->shouldBe(1000);
    }
}