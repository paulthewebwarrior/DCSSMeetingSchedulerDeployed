using MySqlConnector;
using System;
using System.Collections.Generic;
using System.Text;

namespace DCSSMeetingScheduler.Shared {
    public class Connection {

        private static string connectionString = "server=cim-1101.c7uouqm8yqvw.ap-southeast-2.rds.amazonaws.com;database=dcss_db;username=admin;pwd=PaulTheWebWarrior;";

        public static MySqlConnection GetConnection() {
            MySqlConnection conn = new MySqlConnection(connectionString);
            conn.Open();

            return conn;
        }
    }
}
