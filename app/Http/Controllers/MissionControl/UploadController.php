<?php 
namespace SpaceXStats\Http\Controllers\MissionControl;

use SpaceXStats\Http\Controllers\Controller;
use Abraham\TwitterOAuth\TwitterOAuth;
use LukeNZ\Reddit\Reddit;

class UploadController extends Controller {

	public function show() {

        JavaScript::put([
            'tags' => Tag::all(),
            'missions' => Mission::with('featuredImage')->get(),
            'publishers' => Publisher::all()
        ]);

		return view('missionControl.create');
	}	

	// AJAX POST
	public function upload()
    {
        if (!empty(Input::all())) {

            $files = Input::file('file');
            $upload = Upload::check($files);

            if ($upload->hasErrors()) {
                return response()->json(['errors' => $upload->getErrors()], 400);
            }

            $objects = $upload->create();
            return response()->json(['objects' => $objects]);
        }
        return response()->json(false, 400);
    }

	// AJAX POST
	public function submit() {
    	// File Submissions
		if (Request::header('Submission-Type') == 'files') {
            $files = Input::get('data');
            $objectValidities = [];
            $doesNotContainErrors = true;

            // Find each object from file
            for ($i = 0; $i < count($files); $i++) {

                $objectManagers[$i] = App::make('SpaceXStats\Managers\Objects\ObjectFromFile');
                $objectValidities[$i] = $objectManagers[$i]->isValid($files[$i]) ? true : $objectManagers[$i]->getErrors();

                if ($objectValidities[$i] !== true) {
                    $doesNotContainErrors = false;
                }
            }

            // Check if there are errors, if no, add all to db, if yes, return with errors.
            if ($doesNotContainErrors) {
                // add all objects to db
                for ($i = 0; $i < count($files); $i++) {
                    $objectManagers[$i]->create();
                }
            } else {
                return response()->json($objectValidities, 400);
            }
        } else {
            switch (Request::header('Submission-Type')) {

                case 'article':
                    $objectCreator = App::make('SpaceXStats\Managers\Objects\ObjectFromArticle');
                    break;

                case 'pressrelease':
                    $objectCreator = App::make('SpaceXStats\Managers\Objects\ObjectFromPressRelease');
                    break;

                case 'redditcomment':
                    $objectCreator = App::make('SpaceXStats\Managers\Objects\ObjectFromRedditComment');
                    break;

                case 'NSFcomment':
                    $objectCreator = App::make('SpaceXStats\Managers\Objects\ObjectFromNSFComment');
                    break;

                case 'text':
                    $objectCreator = App::make('SpaceXStats\Managers\Objects\ObjectFromText');
                    break;
            }

            if ($objectCreator->isValid(Input::get('data'))) {
                // Add to db
                $objectCreator->create();

            } else {
                return response()->json($objectCreator->getErrors(), 400);
            }
        }

        // redirect to mission control
        Session::flash('flashMessage', array(
            'contents' => 'Done! Your submitted content will be reviewed and published within 24 hours',
            'type' => 'success'
        ));
        return response()->json(true);
	}

    // AJAX GET
    public function retrieveTweet() {
        $twitter = new TwitterOAuth(Credential::TwitterConsumerKey, Credential::TwitterConsumerSecret, Credential::TwitterAccessToken, Credential::TwitterAccessSecret);
        $tweet = $twitter->get('statuses/show', array('id' => Input::get('id')));

        return response()->json($tweet);
    }

    // AJAX GET
    public function retrieveRedditComment() {
        $reddit = new Reddit(Credential::RedditUsername, Credential::RedditPassword, Credential::RedditID, Credential::RedditSecret);

        $comment = $reddit->getComment(Input::get('url'));
        return response()->json($comment);
    }

}
 