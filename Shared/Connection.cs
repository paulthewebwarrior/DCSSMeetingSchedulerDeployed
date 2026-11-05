using MySqlConnector;
using System;
using System.Collections.Generic;
using System.Text;

namespace DCSSMeetingScheduler.Shared {
    public class Connection {

        private static string connectionString = Environment.GetEnvironmentVariable("DCSS_DB_CONNECTION_STRING")
            ?? throw new InvalidOperationException("Set the DCSS_DB_CONNECTION_STRING environment variable before connecting to the database.");

        public static MySqlConnection GetConnection() {
            MySqlConnection conn = new MySqlConnection(connectionString);
            conn.Open();

            return conn;
        }
    }
}
