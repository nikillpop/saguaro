<?php
/*

    Generate a thread based on the given OP #.

    Example:
        1 is an OP
        2 is a reply to OP 1
        3 is a reply to OP 1
        4 is an OP

    $sample = new Thread;
    $sample->format(1); //Format the first OP.
    $sample->format(2); //Will succeed, but only attempt to format that as an OP then fail (since replies can't have replies).

*/

//require("../../config.php"); //In a real environment this wouldn't be needed as it would be inherited from its parent.
require("post.php");

class Thread {
    public $inIndex = false;

    function format($op) {
        global $log;

        $temp = $this->generateOP($log[$op]);
        $temp .= $this->generateReplies($op);

        return $temp;
    }

    function generateOP($input) {
        $post = new Post;
        $post->inIndex = $this->inIndex;
        $post->data = $input;

        return $post->formatOP();
    }

    function generateReplies($op) {
        global $log;

        //Identify replies.
        $temp = "";
        $temp_a = [];
        $temp_l = 0;
        $omit_replies = 0;
        $omit_images = 0;
        $post = "post";
        $image = "image";

        foreach ($log as $entry) {
            if ($entry["resto"] == $op) {
                array_push($temp_a, $entry);
            }
        }

        if (!function_exists('no_compare')) { //Wow so strict.
            function no_compare($a, $b) {
                if ($a['no'] == $b['no']) { return 0; }
                return ($a['no'] < $b['no']) ? -1 : 1;
            }
        }

        usort($temp_a, "no_compare");
        $temp_l = count($temp_a);

        if ($this->inIndex) { //If in index, slice out the latest S_OMITT_NUM replies and calculate omitted replies.
            $temp_b = array_splice($temp_a, 0, -5);
            foreach ($temp_b as $entry) {
                if (strlen($entry['fname']) > 0) $omit_images++;
            }

            $temp_a = array_slice($temp_a, S_OMITT_NUM * -1);
            $omit_replies = $temp_l - count($temp_a);
        }

        if ($omit_replies > 0) { //Omitted replies.
            $post .= ($omit_replies == 1) ? "" : "s";
            $image .= ($omit_images == 1) ? "" : "s";
            $and_images = ($omit_images > 0) ? "and $omit_images $image" : "";

            $temp .= "<span class='omittedposts'>$omit_replies $post $and_images omitted. Click <a href='" . RES_DIR . $op . PHP_EXT . "#" . $op . "'> " . S_REPLY . "</a> to view.</span>";
        }


        foreach ($temp_a as $entry) {
            $temp .= $this->generateReply($entry);
        }

        return $temp;
    }

    function generateReply($input) {
        $post = new Post;
        $post->data = $input;
        $post->inIndex = $this->inIndex;

        return $post->format();
    }
}
?>