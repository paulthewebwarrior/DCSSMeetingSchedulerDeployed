using MySqlConnector;
using System;
using System.Collections.Generic;
using System.Text;

namespace DCSSMeetingScheduler.Shared {
    public class Connection {

        private static string connectionString = "server=148.222.53.51;database=u312211237_dcss_db;username=u312211237_dbuser;password=Paulgabrielcamacho21;";

        public static MySqlConnection GetConnection() {
            MySqlConnection conn = new MySqlConnection(connectionString);
            conn.Open();

            return conn;
        }
    }
}
