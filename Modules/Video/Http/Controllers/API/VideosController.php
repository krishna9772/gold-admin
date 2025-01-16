<?php

namespace Modules\Video\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Video\Models\Video;
use Modules\Entertainment\Models\Watchlist;
use Modules\Video\Transformers\VideoResource;
use Modules\Video\Transformers\VideoDetailResource;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\EntertainmentDownload;
use Illuminate\Support\Facades\Cache;


class VideosController extends Controller
{
    public function videoList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $videoList = Video::query()->with('VideoStreamContentMappings', 'plan');

        $videoList = $videoList->where('status', 1);

        $videoData = $videoList->orderBy('updated_at', 'desc')->paginate($perPage);

        $responseData = VideoResource::collection($videoData);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $videosData) {
                $userId = auth()->id();
                if ($userId) {
                    $isInWatchList = WatchList::where('entertainment_id', $videosData['id'])
                        ->where('user_id', $userId)
                        ->where('type','video')
                        ->exists();

                    // Set the flag in the video data
                    $videosData['is_watch_list'] = $isInWatchList ? true : false;
                }
                $html .= view('frontend::components.card.card_video', ['data' => $videosData])->render();
            }

            $hasMore = $videoData->hasMorePages();

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.tvshow_list'),
                'hasMore' => $hasMore,
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('video.video_list'),
        ], 200);
    }

  public function videoDetails(Request $request){

            $video = Video::with('VideoStreamContentMappings','plan')->where('id', $request->video_id)->first();

            if($request->has('user_id')){
                $user_id = $request->user_id;
                $continueWatch = ContinueWatch::where('entertainment_id', $video->id)->where('user_id', $user_id)->where('entertainment_type', 'video')->first();
                $video['continue_watch'] = $continueWatch;

                $video['is_watch_list'] = WatchList::where('entertainment_id',$request->video_id )->where('user_id', $user_id)->where('profile_id', $request->profile_id)
                ->where('type', 'video')->exists();
                $video['is_likes'] = Like::where('entertainment_id', $request->video_id)->where('type', 'video')->where('user_id', $user_id)->where('profile_id', $request->profile_id)
                ->where('is_like', 1)->exists();
                $video['is_download'] = EntertainmentDownload::where('entertainment_id', $request->video_id)->where('device_id',$request->device_id)->where('user_id', $user_id)
                ->where('entertainment_type', 'video')->where('is_download', 1)->exists();
            }

            $responseData = new VideoDetailResource($video);


      return response()->json([
          'status' => true,
          'data' => $responseData,
          'message' => __('video.video_details'),
      ], 200);
  }
}
