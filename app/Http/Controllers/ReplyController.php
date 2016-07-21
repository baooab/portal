<?php

namespace Lio\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Lio\Forum\Thread;
use Lio\Forum\ThreadRepository;
use Lio\Replies\ReplyRequest;
use Lio\Replies\Reply;
use Lio\Replies\ReplyAble;
use Lio\Replies\ReplyRepository;

class ReplyController extends Controller
{
    /**
     * @var \Lio\Replies\ReplyRepository
     */
    private $replies;

    /**
     * @var \Lio\Forum\ThreadRepository
     */
    private $threads;

    public function __construct(ReplyRepository $replies, ThreadRepository $threads)
    {
        $this->threads = $threads;
        $this->replies = $replies;

        $this->middleware('auth');
    }

    public function store(ReplyRequest $request)
    {
        $replyAble = $this->findReplyAble($request->get('replyable_id'), $request->get('replyable_type'));

        $this->replies->create($replyAble, auth()->user(), $request->get('body'), ['ip' => $request->ip()]);

        return $this->redirectToReplyAble($replyAble);
    }

    public function edit(Reply $reply)
    {
        $this->authorize('update', $reply);

        return view('replies.edit', compact('reply'));
    }

    public function update(ReplyRequest $request, Reply $reply)
    {
        $this->authorize('update', $reply);

        $this->replies->update($reply, $request->only('body'));

        return $this->redirectToReplyAble($reply->replyAble());
    }

    public function delete(Reply $reply)
    {
        $this->authorize('delete', $reply);

        $this->replies->delete($reply);

        return $this->redirectToReplyAble($reply->replyAble());
    }

    private function findReplyAble(int $id, string $type): ReplyAble
    {
        switch ($type) {
            case Thread::TYPE:
                return $this->threads->find($id);
        }

        abort(404);
    }

    private function redirectToReplyAble(ReplyAble $replyAble): RedirectResponse
    {
        if ($replyAble instanceof Thread) {
            return redirect()->route('thread', $replyAble->slug());
        }

        abort(404);
    }
}