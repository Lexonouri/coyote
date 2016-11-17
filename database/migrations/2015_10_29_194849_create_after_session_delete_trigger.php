<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAfterSessionDeleteTrigger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('
CREATE FUNCTION before_session_delete() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
	IF OLD.user_id IS NOT NULL THEN
	    UPDATE users SET ip = OLD.ip, browser = OLD.browser, visited_at = CURRENT_TIMESTAMP(0), visits = visits + 1 WHERE "id" = OLD.user_id;
	   
	    WITH rows AS (UPDATE session_log SET updated_at = CURRENT_TIMESTAMP(0), ip = OLD.ip, url = OLD.url, browser = OLD.browser, robot = OLD.robot WHERE user_id = OLD.user_id RETURNING 1)
	    SELECT COUNT(*) INTO affected FROM rows;
			
		IF affected = 0 THEN
		    INSERT INTO session_log (user_id, ip, url, browser, robot) VALUES(OLD.user_id, OLD.ip, OLD.url, OLD.browser, OLD.robot);
		END IF;
	ELSE
	    WITH rows AS (UPDATE session_log SET updated_at = CURRENT_TIMESTAMP(0), ip = OLD.ip, url = OLD.url, browser = OLD.browser, robot = OLD.robot WHERE id = OLD.id RETURNING 1)
		SELECT COUNT(*) INTO affected FROM rows;
			
		IF affected = 0  THEN
			INSERT INTO session_log (id, ip, url, browser, robot) VALUES(OLD.id, OLD.ip, OLD.url, OLD.browser, OLD.robot);
		END IF;
	END IF;

	RETURN NEW;
END;$$;

CREATE TRIGGER before_session_delete AFTER DELETE ON sessions FOR EACH ROW EXECUTE PROCEDURE "before_session_delete"();
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS "before_session_delete" ON sessions;');
        DB::unprepared('DROP FUNCTION before_session_delete();');
    }
}
