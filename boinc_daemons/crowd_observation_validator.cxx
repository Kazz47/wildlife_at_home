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

// sample_work_generator.cpp: an example BOINC work generator.
// This work generator has the following properties
// (you may need to change some or all of these):
//
// - Runs as a daemon, and creates an unbounded supply of work.
//   It attempts to maintain a "cushion" of 100 unsent job instances.
//   (your app may not work this way; e.g. you might create work in batches)
// - Creates work for the application "example_app".
// - Creates a new input file for each job;
//   the file (and the workunit names) contain a timestamp
//   and sequence number, so they're unique.


#include <iostream>
#include <unistd.h>
#include <cstdlib>
#include <string>
#include <cstring>
#include <sstream>
#include <cmath>
#include <fstream>
#include <ostream>
#include <iomanip>

#include "boinc_db.h"
#include "error_numbers.h"
#include "backend_lib.h"
#include "parse.h"
#include "util.h"
#include "svn_version.h"

#include "sched_config.h"
#include "sched_util.h"
#include "sched_msgs.h"
#include "str_util.h"

#include "mysql.h"

#include "undvc_common/arguments.hxx"
#include "undvc_common/file_io.hxx"

#define CUSHION 2000
// maintain at least this many unsent results
#define REPLICATION_FACTOR  1

const char* app_name = NULL;
const char* out_template_file = "wildlife_out.xml";

DB_APP app;
int start_time;
int seqno;

using namespace std;

class Observation {
    public:
        const int id;
        const int bird_leave;
        const int bird_return;
        const int bird_presence;
        const int bird_absence;
        const int predator_presence;
        const int nest_defense;
        const int nest_success;
        const int video_issue;
        const int chick_presence;
        const int interesting;
        const string status;
        string new_status;
        const double awarded_credit;
        double new_awarded_credit;
        const double accuracy_rating;
        double new_accuracy_rating;
        const int user_id;
        const int video_segment_id;

        int marks[8];

        Observation(int id,
                int bird_leave,
                int bird_return,
                int bird_presence,
                int bird_absence,
                int predator_presence,
                int nest_defense,
                int nest_success,
                int video_issue,
                int chick_presence,
                int interesting,
                string status,
                double awarded_credit,
                double accuracy_rating,
                int user_id,
                int video_segment_id) : id(id), bird_leave(bird_leave), bird_return(bird_return), bird_presence(bird_presence), bird_absence(bird_absence),
        predator_presence(predator_presence), nest_defense(nest_defense), nest_success(nest_success),
        video_issue(video_issue), chick_presence(chick_presence), interesting(interesting),
        status(status), awarded_credit(awarded_credit), accuracy_rating(accuracy_rating), user_id(user_id), video_segment_id(video_segment_id) {

            new_accuracy_rating = 0.0;
            new_awarded_credit = 0.0;

            marks[0] = bird_leave;
            marks[1] = bird_return;
            marks[2] = bird_presence;
            marks[3] = bird_absence;
            marks[4] = predator_presence;
            marks[5] = nest_defense;
            marks[6] = nest_success;
            marks[7] = chick_presence;
        }

        string to_string() {
            //            log_messages.printf(MSG_DEBUG, "id: %d, bird_leave: %d, bird_return: %d, bird_presence: %d, bird_absence: %d, predator_presence: %d, nest_defense: %d, nest_success: %d, video_issue: %d, chick_presence: %d, interesting: %d, status: %s, video_segment_id: %d\n", id, bird_leave, bird_return, bird_presence, bird_absence, predator_presence, nest_defense, nest_success, video_issue, chick_presence, interesting, status.c_str(), video_segment_id);
            ostringstream oss;

            oss 
                << setw(11) << status << " -> " << setw(11) << new_status
                << " ("         << setw(4) << awarded_credit  << " -> " << new_awarded_credit << "c)"
                << " ("         << setw(4) << accuracy_rating << " -> " << new_accuracy_rating << " %)"
                << "  BL: "     << setw(2) << bird_leave
                << ", BR: "     << setw(2) << bird_return
                << ", BP: "     << setw(2) << bird_presence
                << ", BA: "     << setw(2) << bird_absence
                << ", PP: "     << setw(2) << predator_presence
                << ", ND: "     << setw(2) << nest_defense
                << ", NS: "     << setw(2) << nest_success
                << ", VI: "     << setw(2) << video_issue
                << ", CP: "     << setw(2) << chick_presence
                << ", INT: "    << setw(2) << interesting
                << ", id: "     << setw(8) << id
                << ", uid: "    << user_id 
                << ", vsid: "   << video_segment_id;

            return oss.str();
        }

        double matches_canonical_marks(int canonical_marks[]) {
            double match_count = 0;
            for (int i = 0; i < 8; i++) {
                if (canonical_marks[i] < -1 || canonical_marks[i] > 1) continue;    //for inconclusive marks we can still grant partial
                                                                                    //credit for the agreed on marks.
                if (marks[i] == canonical_marks[i]) match_count += 1.0;
                else if (marks[i] == 0 || canonical_marks[i] == 0) match_count += 0.5;
            }
            return match_count;
        }

};


void get_mark_totals(const vector< Observation* > &observations, int total_yes[], int total_no[], int total_unsure[]) {
    for (int i = 0; i < 8; i++) {
        total_yes[i] = 0;
        total_no[i] = 0;
        total_unsure[i] = 0;
    }

    for (int i = 0; i < (int)observations.size(); i++) {
        for (int j = 0; j < 8; j++) {
            if (observations.at(i)->marks[j] ==  1) total_yes[j]++;
            if (observations.at(i)->marks[j] ==  0) total_unsure[j]++;
            if (observations.at(i)->marks[j] == -1) total_no[j]++;
        }
    }
}

bool has_potential_canonical(int expected_marks[]) {
    //Need to make sure that every mark has a total greater than the other totals
    for (int i = 0; i < 8; i++) {
        if (expected_marks[i] == -2) return false;
    }

    return true;
}

void get_expected_canonical(int total_yes[], int total_no[], int total_unsure[], int expected_marks[]) {
    for (int i = 0; i < 8; i++) {
        expected_marks[i] = -2; //set the mark to -2 if there's no agreed on marking

        if ( total_yes[i] >= 2    && total_yes[i]    > total_no[i]   && total_yes[i]    > total_unsure[i] ) expected_marks[i] = 1;
        if ( total_no[i] >= 2     && total_no[i]     > total_yes[i]  && total_no[i]     > total_unsure[i] ) expected_marks[i] = -1; 
        if ( total_unsure[i] >= 2 && total_unsure[i] > total_no[i]   && total_unsure[i] > total_yes[i]    ) expected_marks[i] = 0;
    }
}


/**
 *  This wrapper makes for much more informative error messages when doing MYSQL queries
 */
#define mysql_query_check(conn, query) __mysql_check (conn, query, __FILE__, __LINE__)

void __mysql_check(MYSQL *conn, string query, const char *file, const int line) {
    mysql_query(conn, query.c_str());

    if (mysql_errno(conn) != 0) {
        ostringstream ex_msg;
        ex_msg << "ERROR in MySQL query: '" << query.c_str() << "'. Error: " << mysql_errno(conn) << " -- '" << mysql_error(conn) << "'. Thrown on " << file << ":" << line;
        cerr << ex_msg.str() << endl;
        exit(1);
    }   
}

/**
 *  The following opens a database connection to the remote database on wildlife.und.edu
 */
MYSQL *wildlife_db_conn = NULL;
MYSQL *boinc_db_conn = NULL;

void initialize_wildlife_database() {
    wildlife_db_conn = mysql_init(NULL);

    //shoud get database info from a file
    string db_host, db_name, db_password, db_user;
    ifstream db_info_file("../wildlife_db_info");

    db_info_file >> db_host >> db_name >> db_user >> db_password;
    db_info_file.close();

    /*
       cout << "parsed db info:" << endl;
       cout << "\thost: " << db_host << endl;
       cout << "\tname: " << db_name << endl;
       cout << "\tuser: " << db_user << endl;
       cout << "\tpass: " << db_password << endl;
       */

    if (mysql_real_connect(wildlife_db_conn, db_host.c_str(), db_user.c_str(), db_password.c_str(), db_name.c_str(), 0, NULL, 0) == NULL) {
        log_messages.printf(MSG_CRITICAL, "Error connecting to database: %d, '%s'\n", mysql_errno(wildlife_db_conn), mysql_error(wildlife_db_conn));
        exit(1);
    }   
}

void initialize_boinc_database() {
    int retval = config.parse_file();
    if (retval) {
        log_messages.printf(MSG_CRITICAL, "Can't parse config.xml: %s\n", boincerror(retval));
        exit(1);
    }

    retval = boinc_db.open( config.db_name, config.db_host, config.db_user, config.db_passwd );
    if (retval) {
        log_messages.printf(MSG_CRITICAL, "can't open db\n");
        exit(1);
    }

    boinc_db_conn = boinc_db.mysql;
}


void usage(char *name) {
    fprintf(stderr, "This is an example BOINC work generator.\n"
            "This work generator has the following properties\n"
            "(you may need to change some or all of these):\n"
            "  It attempts to maintain a \"cushion\" of 100 unsent job instances.\n"
            "  (your app may not work this way; e.g. you might create work in batches)\n"
            "- Creates work for the application \"example_app\".\n"
            "- Creates a new input file for each job;\n"
            "  the file (and the workunit names) contain a timestamp\n"
            "  and sequence number, so that they're unique.\n\n"
            "Usage: %s [OPTION]...\n\n"
            "Options:\n"
            "  --app X                      Application name (default: example_app)\n"
            "  --out_template_file          Output template (default: example_app_out)\n"
            "  [ -d X ]                     Sets debug level to X.\n"
            "  [ -h | --help ]              Shows this help text.\n"
            "  [ -v | --version ]           Shows version information.\n",
            name
           );
}

int main(int argc, char** argv) {
    for (int i = 1; i < argc; i++) {
        if (is_arg(argv[i], "d")) {
            if (!argv[++i]) {
                log_messages.printf(MSG_CRITICAL, "%s requires an argument\n\n", argv[--i]);
                usage(argv[0]);
                exit(1);
            }
            int dl = atoi(argv[i]);
            log_messages.set_debug_level(dl);
            if (dl == 4) g_print_queries = true;
        }
    }

    vector<string> arguments(argv, argv + argc);

    bool no_db_update = argument_exists(arguments, "--no_db_update");

    initialize_boinc_database();
    initialize_wildlife_database();

    while (true) { //loop forever
        //This checks to see if there is a stop in place, if there is it will exit the work generator.
        check_stop_daemons();

        ostringstream unvalidated_video_query;
        //    unvalidated_video_query << "SELECT id FROM video_segment_2 WHERE crowd_obs_count > 0"; // << " AND crowd_status != 'VALIDATED'";
//        unvalidated_video_query << "SELECT id, duration_s, species_id, location_id FROM video_segment_2 WHERE (crowd_obs_count >= required_views AND crowd_status = 'WATCHED') AND validate_for_review != true";
        unvalidated_video_query << "SELECT id, duration_s, species_id, location_id FROM video_segment_2 WHERE (crowd_obs_count >= required_views AND crowd_status = 'WATCHED')";

        mysql_query_check(wildlife_db_conn, unvalidated_video_query.str());
        MYSQL_RES *video_segment_result = mysql_store_result(wildlife_db_conn);

        int count = 0;

        bool progress_updated = false;

        MYSQL_ROW video_segment_row;
        while ((video_segment_row = mysql_fetch_row(video_segment_result)) != NULL) {

            int video_segment_id = atoi(video_segment_row[0]);
            int duration_s = atoi(video_segment_row[1]);
            int species_id = atoi(video_segment_row[2]);
            int location_id = atoi(video_segment_row[3]);

            ostringstream observation_query;
            observation_query << "SELECT id, bird_leave, bird_return, bird_presence, bird_absence, predator_presence, nest_defense, nest_success, video_issue, chick_presence, interesting, status, awarded_credit, accuracy_rating, user_id, video_segment_id FROM observations WHERE video_segment_id = " << video_segment_id;

            mysql_query_check(wildlife_db_conn, observation_query.str());
            MYSQL_RES *observation_result = mysql_store_result(wildlife_db_conn);
            MYSQL_ROW observation_row;

            /**
             *  Read the observations for the video segment from the database.
             */
            vector< Observation* > observations;
            while ((observation_row = mysql_fetch_row(observation_result)) != NULL) {
                int id                  = atoi(observation_row[0]);
                int bird_leave          = atoi(observation_row[1]);
                int bird_return         = atoi(observation_row[2]);
                int bird_presence       = atoi(observation_row[3]);
                int bird_absence        = atoi(observation_row[4]);
                int predator_presence   = atoi(observation_row[5]);
                int nest_defense        = atoi(observation_row[6]);
                int nest_success        = atoi(observation_row[7]);
                int video_issue         = atoi(observation_row[8]);
                int chick_presence      = atoi(observation_row[9]);
                int interesting         = atoi(observation_row[10]);
                string status           = observation_row[11];
                double awarded_credit   = atof(observation_row[12]);
                double accuracy_rating  = atof(observation_row[13]);
                int user_id             = atoi(observation_row[14]);
                int video_segment_id    = atoi(observation_row[15]);

                Observation *observation = new Observation(id, bird_leave, bird_return, bird_presence, bird_absence, predator_presence, 
                        nest_defense, nest_success, video_issue, chick_presence, interesting,
                        status, awarded_credit, accuracy_rating, user_id, video_segment_id);

                observations.push_back(observation);
            }

            /**
             *  Check to see if there was an expert observation
             *  If there is, that is the canonical result.
             */
            int canonical = -1;
            bool has_expert = false;
            for (uint32_t i = 0; i < observations.size(); i++) {
                if (0 == observations[i]->status.compare("EXPERT")) {
                    canonical = i;
                    has_expert = true;
//                    log_messages.printf(MSG_DEBUG, "FOUND AN EXPERT OBSERVATION!\n");
//                    exit(0);
                }
            }

            int total_yes[8], total_no[8], total_unsure[8], canonical_marks[8];

            if (canonical >= 0) {
                for (int i = 0; i < 8; i++) {
                    canonical_marks[i] = observations[canonical]->marks[i];
                }
            } else  {
                //No canonical observation yet, see if
                //we can create one.

                //1. total up the yes/no/unsure for each observation marking
                get_mark_totals(observations, total_yes, total_no, total_unsure);

                //2. get what marks we would expect the canonical result to have
                get_expected_canonical(total_yes, total_no, total_unsure, canonical_marks);

                //3. check to see if there's a possible canonical result
                if (has_potential_canonical(canonical_marks)) {
                    //4. find an observation which matches the expected canonical marks
                    for (int i = 0; i < (int)observations.size(); i++) {
                        if (observations[i]->matches_canonical_marks(canonical_marks) == 8.0) {
                            canonical = i;
                            break;
                        }
                    }
                }
            }

            log_messages.printf(MSG_DEBUG, "number_observations: %d\n", (int)observations.size());
            ostringstream expected_marks_oss, yes_oss, no_oss, unsure_oss;
            for (int i = 0; i < 8; i++) {
                expected_marks_oss << " " << setw(4) << canonical_marks[i];
                yes_oss << " " << setw(4) << total_yes[i];
                no_oss << " " << setw(4) << total_no[i];
                unsure_oss << " " << setw(4) << total_unsure[i];

                if (canonical_marks[i] < -1 && !has_expert) canonical = -1; 
            }
            log_messages.printf(MSG_DEBUG, "total yes      : %s\n", yes_oss.str().c_str());
            log_messages.printf(MSG_DEBUG, "total no       : %s\n", no_oss.str().c_str());
            log_messages.printf(MSG_DEBUG, "total unsure   : %s\n", unsure_oss.str().c_str());
            log_messages.printf(MSG_DEBUG, "canonical marks: %s\n", expected_marks_oss.str().c_str());
            log_messages.printf(MSG_DEBUG, "canonical      : %d\n", canonical);

            //There was a canoical result, so update the status of each
            //observation.
            for (uint32_t i = 0; i < observations.size(); i++) {
                ostringstream oss;

                if (0 != observations[i]->status.compare("UNVALIDATED")) progress_updated = true;
            }

            for (uint32_t i = 0; i < observations.size(); i++) {
                ostringstream oss;

                if (canonical < 0) {
                    observations[i]->new_status = "INCONCLUSIVE";

                } else if (observations.size() >= 5 && canonical < 0) {
                    observations[i]->new_status = "INCONCLUSIVE";

                } else if (canonical == (int)i) {
                    if (0 == observations[i]->status.compare("EXPERT")) {
                        observations[i]->new_status = "EXPERT";
                    } else {
                        observations[i]->new_status = "CANONICAL";
                    }
                } else if (observations[i]->matches_canonical_marks(canonical_marks) == 8.0) {
                    observations[i]->new_status = "VALID";
                } else {
                    observations[i]->new_status = "INVALID";
                }

                if (canonical >= 0 || observations.size() >= 5) {
                    observations[i]->new_accuracy_rating = (double)observations[i]->matches_canonical_marks(canonical_marks) / 8.0;
                    observations[i]->new_awarded_credit  = duration_s * ((double)observations[i]->matches_canonical_marks(canonical_marks) / 8.0);
                }
            }

            //Print out the observations for debugging
            for (uint32_t i = 0; i < observations.size(); i++) {
                ostringstream oss;
                oss << "RATING: " << setw(8) << observations[i]->matches_canonical_marks(canonical_marks) << ", ";
                oss << observations.at(i)->to_string();

                log_messages.printf(MSG_DEBUG, "%s\n", oss.str().c_str());
            }
            log_messages.printf(MSG_DEBUG, "\n");
            log_messages.printf(MSG_DEBUG, "Progress already updated: %d\n", progress_updated);

            /*
            if (count < 100) {
                count++;
                continue;
            } else {
                exit(1);
            }
            */

            if (observations.size() >= 5 || canonical >= 0) {
                for (uint32_t i = 0; i < observations.size(); i++) {
                    ostringstream user_query, observation_query, team_query;

                    double accuracy_difference = observations[i]->new_accuracy_rating - observations[i]->accuracy_rating;
                    double credit_difference = observations[i]->new_awarded_credit - observations[i]->awarded_credit;

                    //the status or credit of the observation hasn't changed so we can skip it
                    if (!observations[i]->new_status.compare(observations[i]->status) && accuracy_difference == 0.0 && credit_difference == 0.0) continue;

                    user_query << "UPDATE user SET bossa_accuracy = bossa_accuracy + " << accuracy_difference << ", bossa_total_credit = bossa_total_credit + " << credit_difference << " WHERE id = " << observations[i]->user_id;

                    log_messages.printf(MSG_DEBUG, "%s\n", user_query.str().c_str());
                    if (!no_db_update) mysql_query_check(boinc_db_conn, user_query.str());

                    team_query << "UPDATE team SET bossa_accuracy = bossa_accuracy + " << accuracy_difference << ", bossa_total_credit = bossa_total_credit + " << credit_difference << " WHERE id = (SELECT teamid FROM user WHERE id =" << observations[i]->user_id << ")";

                    log_messages.printf(MSG_DEBUG, "%s\n", team_query.str().c_str());
                    if (!no_db_update) mysql_query_check(boinc_db_conn, team_query.str());


                    observation_query << "UPDATE observations SET status = '" << observations[i]->new_status << "', awarded_credit = " << observations[i]->new_awarded_credit << ", accuracy_rating = " << observations[i]->new_accuracy_rating << " WHERE id = " << observations[i]->id;
                    log_messages.printf(MSG_DEBUG, "%s\n", observation_query.str().c_str());
                    if (!no_db_update) mysql_query_check(wildlife_db_conn, observation_query.str());
                }
            }


            if (canonical < 0) {
                //No canonical observation has been found yet
                ostringstream vs2_query;

                vs2_query << "UPDATE video_segment_2 SET required_views = IF(required_views < 5, required_views + 1, 5)";
                if (observations.size() >= 5) vs2_query << ", crowd_status = 'NO_CONSENSUS'";
                vs2_query << " WHERE id = " << video_segment_id;

                log_messages.printf(MSG_DEBUG, "%s\n", vs2_query.str().c_str());
                if (!no_db_update) mysql_query_check(wildlife_db_conn, vs2_query.str());

                if (observations.size() >= 5) {
                    //If there's no consensus, automatically flag the video for expert review
                    ostringstream report_oss;
                    report_oss << "REPLACE INTO reported_video SET video_segment_id = " << video_segment_id << ", reporter_id = -1, reporter_name='VALIDATOR', report_comments='This video was automatically reported because the user observations were inconclusive.'";
                    log_messages.printf(MSG_DEBUG, "%s\n", report_oss.str().c_str());
                    if (!no_db_update) mysql_query_check(wildlife_db_conn, report_oss.str());

                    ostringstream status_oss;
                    status_oss << "UPDATE video_segment_2 SET report_status = IF(report_status = 'UNREPORTED', 'REPORTED', report_status) WHERE id = " << video_segment_id;
                    log_messages.printf(MSG_DEBUG, "%s\n", status_oss.str().c_str());
                    if (!no_db_update) mysql_query_check(wildlife_db_conn, status_oss.str());

                    ostringstream waiting_oss;
                    waiting_oss << "UPDATE species SET waiting_review = waiting_review + 1 WHERE id = " << species_id;
                    log_messages.printf(MSG_DEBUG, "%s\n", waiting_oss.str().c_str());
                    if (!no_db_update) mysql_query_check(wildlife_db_conn, waiting_oss.str());
                }

            } else {
                ostringstream vs2_query;
                vs2_query << "UPDATE video_segment_2 SET crowd_status = 'VALIDATED' WHERE id = " << video_segment_id;

                log_messages.printf(MSG_DEBUG, "%s\n", vs2_query.str().c_str());
                if (!no_db_update) mysql_query_check(wildlife_db_conn, vs2_query.str());
            }

            /**
             *  UPDATE: Now updating progress at the end of the loop, along with available video
             *
             *  Should only update the progress if it hasn't been updated for this video yet.
            if (!progress_updated) {
                ostringstream progress_query;
                progress_query << "UPDATE progress SET validated_video_s = validated_video_s + " << duration_s << " WHERE progress.species_id = " << species_id << " AND progress.location_id = " << location_id;

                log_messages.printf(MSG_DEBUG, "%s\n", progress_query.str().c_str());
                if (!no_db_update) mysql_query_check(wildlife_db_conn, progress_query.str());
            }
             */

            for (uint32_t i = 0; i < observations.size(); i++) {
                delete observations[i];
            }

            count++;
        }

        /**
         *  Update the progess table with new amounts of validated video
         */
        if (progress_updated) {
            log_messages.printf(MSG_DEBUG, "Updating progress...\n");
            ostringstream validated_progress_query;
            validated_progress_query << "UPDATE progress AS p SET validated_video_s = (SELECT SUM(duration_s) FROM video_segment_2 AS vs2 WHERE vs2.species_id = p.species_id AND vs2.location_id = p.location_id AND vs2.crowd_status = 'VALIDATED');";

            log_messages.printf(MSG_DEBUG, "%s\n", validated_progress_query.str().c_str());
            if (!no_db_update) mysql_query_check(wildlife_db_conn, validated_progress_query.str());
        }

        /**
         *  Update the progress table with new available video times.
         */
        log_messages.printf(MSG_DEBUG, "Updating progress...\n");
        ostringstream available_progress_query;
        available_progress_query << "UPDATE progress AS p SET available_video_s = (SELECT SUM(duration_s) FROM video_segment_2 AS vs2 WHERE vs2.species_id = p.species_id AND vs2.location_id = p.location_id AND vs2.processing_status = 'DONE' AND vs2.release_to_public = true);";

        log_messages.printf(MSG_DEBUG, "%s\n", available_progress_query.str().c_str());
        if (!no_db_update) mysql_query_check(wildlife_db_conn, available_progress_query.str());

        log_messages.printf(MSG_DEBUG, "Sleeping...\n"); 
        sleep(300);
    }
}


