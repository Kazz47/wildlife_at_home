<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2008 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

$cwd = __FILE__;
if (is_link($cwd)) $cwd = readlink($cwd);
$cwd = dirname($cwd);

require_once("../inc/db.inc");
require_once("../inc/util.inc");
require_once("../inc/news.inc");
require_once("../inc/cache.inc");
require_once("../inc/uotd.inc");
require_once("../inc/sanitize_html.inc");
require_once("../inc/translation.inc");
require_once("../inc/text_transform.inc");
require_once("../project/project.inc");

require_once($cwd . "/navbar.php");
require_once($cwd . "/footer.php");

$caching = false;

if ($caching) {
    start_cache(INDEX_PAGE_TTL);
}

$stopped = web_stopped();
$rssname = "Wildlife@Home RSS 2.0" ;
$rsslink = "http://volunteer.cs.und.edu/wildlife/rss_main.php";

header("Content-type: text/html; charset=utf-8");

echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";

$bootstrap_scripts = file_get_contents($cwd . "/bootstrap_scripts.html");

echo "<html>
    <head>
        <meta charset='utf-8'>
        <title>Wildlife@Home</title>

        <link rel='alternate' type='application/rss+xml' title='Wildlife@Home RSS 2.0' href='http://volunteer.cs.und.edu/wildlife/rss_main.php'>
        <link rel='icon' href='wildlife_favicon_grouewjn3.png' type='image/x-icon'>
        <link rel='shortcut icon' href='wildlife_favicon_grouewjn3.png' type='image/x-icon'>

        <style>
        hr.news_line {
            border: 0;
            border-bottom: 1px solid rgb(200, 200, 200);
        }

        td.news {
            background-color: #dff0ff;
            border-color: #add8e6;
        }

        span.news_title {
            font-weight: bold;
        }

        span.news_date {
            color: rgb(100,100,100);
            font-size: 0.9em;
            float: right;
        }

        body {
            padding-top: 60px;
        }
        @media (max-width: 979px) {
            body {
                padding-top: 0px;
            }
        }
        </style>

        $bootstrap_scripts

        <script>
          !function ($) {
            $(function(){
              // carousel demo
              $('.item').eq(Math.floor((Math.random() * $('.item').length))).addClass('active');
              $('#myCarousel').carousel({ interval: false })
            })
          }(window.jQuery)
        </script>
";

include 'schedulers.txt';
echo "
    </head><body>
";

$active_items = array(
                    'home' => 'active',
                    'watch_video' => '',
                    'message_boards' => '',
                    'preferences' => '',
                    'about_wildlife' => '',
                    'community' => ''
                );

print_navbar($active_items);

echo "
    <div class='container'>
        <div class='row'>
            <div class='span6'>
                <h3>Welcome to Wildlife@Home</h3>
                <p>
                Wildlife@Home is <i>citizen science project</i>, developed in a joint effort by <a href='http://und.edu'>University of North Dakota</a>'s <a href='http://www.cs.und.edu/'>Department of Computer Science</a> and <a href='http://www.und.edu/dept/biology/biology_main.htm'>Department of Biology</a>, aimed at analyzing video gathered from various cameras recording wildlife.  Currently the project is looking at video of <a href='sharptailed_grouse_info.php'>sharp-tailed grouse</a>, <i>Tympanuchus phasianellus</i>, performing their mating dances (lekking), and then examining their nesting habits and ecology. The nest cameras have been set up up both near western North Dakota's oil fields and also within protected state lands. We recently have also begun studying two federally protected species, interior least terns, <i>Sternula antillarum</i>, and piping plovers, <i>Charadruis melodus</i>. </p>
                
                <p>We hope that your participation will help us determine the impact of the oil development on the sharp-tailed grouse, and better understand the behaviors of least terns and piping plovers to aid in their conservation, as well as provide some interesting video for everyone to watch and discuss. Feel free to scroll through our image gallery on the right to get a better idea of what's going on with the project and see the field biologists in action, or talk to us on the <a href='./forum_index.php'>message boards</a>.
                </p>

                <div class='row-fluid'>
                    <a class='btn btn-large btn-primary span6' href='video_selector.php'>Watch Videos</a>
                    <a class='btn btn-large btn-primary span6' href='boinc_instructions.php'>Volunteer Your Computer</a>
                </div>
            </div>

            <div class='span6'>
                <!-- Carousel
                ================================================== -->
                <div id='myCarousel' class='carousel slide'>
                  <div class='carousel-inner'>
                    <div class='item'>
                      <img src='images/HenCapture2.JPG' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>Each spring we capture hens on the lek or dancing grounds where males display for females.  We fit hens with a necklace-style radio transmitter that emits a unique radio frequency which we can use to relocate the bird and find her nest.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/RadioTracking1.JPG' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>We track grouse using radio telemetry receivers and various types of antennas such as this handheld antenna and receiver.  Photo courtesy of Chris Felege.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/Truck.JPG' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>We track grouse using radio telemetry receivers and various type so antennas such as this truck mounted antenna.  Photo courtesy of Anna Mattson.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/camera_system_1.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>Once we find a nest, we install a miniature surveillance camera near the nest.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/camera_system_2.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>Nest cameras are powered by cable, from about 75 feet away with a 12 volt battery and a waterproof box housing a DVR that will record to SD cards.</p>
                        </div>
                      </div>
                    </div>


                    <div class='item'>
                      <img src='images/grouse_nest.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>A sharp-tailed grouse nest.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/hen_on_nest.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>Hens will incubate a nest for about 23 days unless a predator finds and destroys the nest.  This is a snapshot from our nest cameras of hen incubating her nest.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/hatch.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>Sharp-tailed grouse chicks hatching.</p>
                        </div>
                      </div>
                    </div>


                    <div class='item'>
                      <img src='images/hawk2_resized.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>Various predators such as this Swainson’s Hawk will attack the hen while she is sitting on the nest.  This images is from one of our nest cameras.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/badger1.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>Ground predators, such as this American Badger, will destroy the nest.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/chick_capture.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>After the peak of hatch, we follow hens with broods via radio signals.  Once the chicks are about a month old, we will relocate the brood and catch them in big net during the night.  Chicks are then fitted with their own necklace-style radio transmitters so we monitor their survival and reproduction over the next year.</p>
                        </div>
                      </div>
                    </div>

                    <div class='item'>
                      <img src='images/lightning.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>This image found in the collected video shows a lightning crash behind a piping plover incubating a nest on the Missouri River in North Dakota.</p>
                        </div>
                      </div>
                    </div>


                    <div class='item'>
                      <img src='images/plover_chick.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>This image found in the collected video shows a piping plover chick walking across the video screen with an adult brooding the remaining chicks on the nest in the background.</p>
                        </div>
                      </div>
                    </div>


                    <div class='item'>
                      <img src='images/plover_in_flight.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>The leg bands used by wildlife biologists for bird identification are clearly visible on the flying adult piping plover.</p>
                        </div>
                      </div>
                    </div>


                    <div class='item'>
                      <img src='images/tern_chick.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>A tern chick yawns in front of the camera while the adult tern incubates the remaining egg on the nest. This image was found in the collected video.</p>
                        </div>
                      </div>
                    </div>


                    <div class='item'>
                      <img src='images/tern_nest_exchange.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>Interior least terns exhibit biparental investment in their nests. This image found in the collected video shows a nest exchange event between two flying tern parents.</p>
                        </div>
                      </div>
                    </div>


                    <div class='item'>
                      <img src='images/tern_feeding_tern.png' alt=''>
                      <div class='container'>
                        <div class='carousel-caption'>
                          <p>An adult tern feeds a fish to a tern incubating a nest. This image was found in collected video.</p>
                        </div>
                      </div>
                    </div>

                  </div>
                  <a class='left carousel-control' href='#myCarousel' data-slide='prev'>&lsaquo;</a>
                  <a class='right carousel-control' href='#myCarousel' data-slide='next'>&rsaquo;</a>
                </div><!-- /.carousel -->
            </div>
        </div>
    </div>

<hr>

    <div class='container'>
        <div class='row'>
            <div class='span6'>
";

if ($stopped) {
    echo "
        <b>Wildlife@Home is temporarily shut down for maintenance.
        Please try again later</b>.
    ";
} else {
    db_init();
}


/*
if (!$stopped) {
    $profile = get_current_uotd();
    if ($profile) {
        echo "
            <div class='well well-large'>
            <td class=\"uotd\">
            <h2>".tra("User of the day")."</h2>
        ";
        show_uotd($profile);
        echo "</td></tr></div>\n";
    }
}
*/

echo "
    <div class='well well-small'>
    <tr><td class=\"news\">
    <h2>News</h2>
    <p>
";
include("motd.php");
show_news(0, 5);
echo "
    </td>
    </tr></table>
    </div>
";


echo"
            </div> <!-- span -->

            <div class='span6'>
                    <section id='contact'>
                        <div class='page-header'><h2>Contact Information</h2></div>
                        <p>Please email any questions or suggestions to <a href='mailto:tdesell@cs.und.edu'>Travis Desell</a>, or feel free to post a message in our <a href='forum_index.php'>forums</a>.
                        <p>
                        The Wildlife@Home team includes:
                        <ul>
                        <li>
                        <a href='http://people.cs.und.edu/~tdesell/'>Travis Desell</a>, Assistant Professor of Computer Science, University of North Dakota
                        </li>

                        <li>
                        <a href='http://arts-sciences.und.edu/biology/faculty/susan-felege.cfm'>Susan Ellis-Felege</a>, Assistant Professor of Biology, University of North Dakota 
                        </li>

                        <li>
                        Alicia Andes, Graduate Research Assistant in Biology, University of North Dakota
                        </li>

                        <li>
                        Paul Burr, Graduate Research Assistant in Biology, University of North Dakota
                        </li>

                        <li>
                        Becca Eckroad, Undergraduate Research Assistant in Biology, University of North Dakota
                        </li>

                        <li>
                        Kyle Goehner, Graduate Research Assistant in Computer Science, University of North Dakota
                        </li>

                        <li>
                        Julia Johnson, Undergraduate Research Assistant in Biology, University of North Dakota
                        </li>
                        <li>
                        Leila Mohsenian, Undergraduate Research Assistant in Biology, University of North Dakota
                        </li>

                        <li>
                        Aaron Robinson, North Dakota Game and Fish.
                        </li>

                        <li>
                        Terry Shaffer, U.S.G.S. Northern Prairie Wildlife Research Center.
                        </li>
                        <li>
                        Mark Sherfy, U.S.G.S. Northern Prairie Wildlife Research Center.
                        </li>

                        <li>
                        Rebecca VanderClute, Undergraduate Research Assistant in Computer Science, University of North Dakota
                        </li>
                        </ul>

                        With previous support from:
                        <ul>
                        <li>
                        Robert Bergman, Graduate Research Assistant in Computer Science, University of North Dakota
                        </li>

                        <li>
                        Adam Pachl, Undergraduate Research Assistant in Biology, University of North Dakota
                        </li>

                        <li>
                        Nitin Karodiya, Graduate Research Assistant in Computer Science, University of North Dakota
                        </li>

                        <li>
                        Eric Kjeldergaard, Graduate Research Assistant in Computer Science, University of North Dakota
                        </li>
                        </ul>

                        </p>
                    </section>

                    <section id='support'>
                        <div class='page-header'><h2>Support</h2></div>

                        <div class='row-fluid'>
                            <div class='span2'>
                                <p align=center>
                                <a href='http://www.nsf.gov/'><img class ='floating' border='0' src='images/nsf1.png' width ='83' height='83' alt='Funded through a grant from the National Science Foundation (NSF).' /></a>
                                </p>
                            </div>
                            <div class='span10'>
                                <p>
                                    Wildlife@Home is currently being supported by <a href='http://www.nsf.gov/awardsearch/showAward?AWD_ID=1319700&HistoricalAwards=false'>NSF award no. 1319700</a> through the <a href='http://www.nsf.gov/div/index.jsp?div=IIS'>Division of Intelligent Information Systems</a>'s Information Integration and Informatics (III) program.
                                </p>
                            </div>
                        </div>

                        <div class='row-fluid'>
                            <div class='span2'>
                                <p align=center>
                                <a href='http://und.edu'><img src='images/und_logo.png'></a>
                                </p>
                            </div>
                            <div class='span10'>
                            <p>
                                Wildlife@Home has been generously supported by a collaborative research award and new faculty SEED grant from UND's Office of Research Development and Compliance. The project's video streaming server is hosted by UND's <a href='http://crc.und.edu'>Computational Research Center</a> and the volunteer computing server is hosted by UND's <a href='http://www.aero.und.edu/about/SCC.aspx'>Scientific Computing Center</a>.
                            </p>
                            </div>
                        </div>
                        <br>

                        <div class='row-fluid'>
                            <div class='span2'>
                                <a href=\"http://gf.nd.gov\"><img src=\"images/ndgf_logo.png\"></a>
                            </div>
                            <div class='span10'>
                                North Dakota Game and Fish has provided financial support for field logistics to collect sharp-tailed grouse videos.
                            </div>
                        </div>
                        <br>

                        <div class='row-fluid'>
                            <div class='span2'>
                                <a href=\"http://www.npwrc.usgs.gov\"><img src=\"images/usgs_logo.png\"></a>
                            </div>
                            <div class='span10'>
                                USGS has provided financial support for camera equipment, video storage, and field assistance to collect data for the piping plover and interior least tern.
                            </div>
                        </div>
                        <br>

                        <div class='row-fluid'>
                            <div class='span2'>
                                <a href=\"http://boinc.berkeley.edu/\" style='display:block; margin-left:auto; margin-right:auto;'><img src=\"img/pb_boinc.gif\" alt=\"Powered by BOINC\"></a>
                            </div>
                            <div class='span10'>
                                <p>
                                Wildlife@Home is in part powered by the <a href='http://boinc.berkeley.edu/'>Berkeley Open Infrastructure for Network Computing (BOINC)</a>.
                                </p>
                            </div>
                        </div>

                    </section>
            </div> <!-- span -->

        </div> <!-- row -->
    </div>  <!-- container -->
";


if ($caching) {
//    page_tail_main(true);
    end_cache(INDEX_PAGE_TTL);
} else {
//    page_tail_main();
}

echo "<hr>";
print_footer();

echo "</body></html>";

?>
