using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

using System.Net;
using System.IO;
using System.Collections;

using Core;
using DataProvider;

using Newtonsoft.Json;

namespace AFTester
{
	public static class DPTester
	{
		public static void TestQBO()
		{
			string sRealmId = "";
			string sUrlFmt = @"https://v4thirdparty-e2e.api.intuit.com/graphql";
			int iConfigId = -1;
			string sAccessToken = "eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiZGlyIn0..mFAxG7jnBLl2HJV4hjT6-w.hcBuLqIZH56cfbYu5NRzF6XdyISMkXGTGqg2yN4AP7cL-Xq2oDN4Xsj0i5zLE5YlANeTvEwsRQM8xlEbMcohAPi__hlIKkZ0niW6bxlPqeAZxNco8cAe07qhACHOpTmqdfSqDdlsEmc4XkbgI59evy_T6_NRjKF-Um3jb47gq6J_8XqQfD4D2hhwBOmiML3F0WcKycyFf7uhxQDsEK30s20_qPkTtxH9y70hVFOaLop5UAJ58LZSq_qpu2oRs5IewpyPUu9E66IO1NcGM4j1REVTx6dLk9Z8CzKOtQ9G4r3Sz2ShuDf0YJg5-Qr9CpuvYRn-tFWvW8HnPtmJ62Nn1XVV_YRhNTNHkdlJFtR8wufTHx9-F-NyO0NByTWIHJTugX8Me5AFO6sP51jxyJjVrrUvq3D1pLwDTezM1rHo0aEwhm43-FkTJrp7ibUSCrfMf-6M8WJA5XrXxiJzahTLFZOQfAJyAOzDLgZwLLjqqG8M7Fp7-4gCvBg8LLxEaDZhRHzSGtLteqUTj2Hx0HJddSmVq0Xh5sLqOjbV3TN7G5GpAU1ooggp65v0oI_JYm9aSaiGOTA62EkYs3f2pUFn5qlQ8KdR6ImxCitCKGHHxCVlxYzF0ty1vxvcAnQeul07CiSVyH_MfWhwYFWCwbQ773Yu7c0GDKVgC0no1O_Kksy_WjXnkaZqozVtz63zVJBQ.k5jPWcdaM1hGuIhhFhZeng";

/*			
			PersisterArgs pArgs = new PersisterArgs();
			AFCloudPersister persister = new AFCloudPersister(pArgs);
			Config config = persister.LoadSyncConfig(iConfigId);
			sRealmId = creds.OAuth.RealmID;
		
			// refresh the access token
			DPFactory factory = new QboDP.DPFactory();
			factory.Initialize(creds);
			DataProvider.DataProvider dp = factory.CreateCustomerDP();
*/

			string sURL = string.Format(sUrlFmt, sRealmId);

			WebHeaderCollection whc = new WebHeaderCollection();
			whc.Add($"Authorization: Bearer {sAccessToken}");

			string sBody = JsonConvert.ToString(@"query CompanyQuery {
  company {
    bill: transactions (with:""txnTypeFilter = 'expense' && header.txnDate > '2018-06-29'""){

	  edges {
				node {
          id
header{
contact {
id
}
}
          lines {
            itemLines {
              edges {
                node {
                  id
                }
              }
            }
          }
        }
			}
		}
	}
}
");
			sBody = $"{{\"query\":{sBody},\"variables\":null}}";

			HttpHandlerTool.DataStructures.AfHttpResponse res = HttpHandlerTool.HttpHandler.QuickHttpCall(sURL, whcHeaders: whc, sMethod: "POST", sBody: sBody);
			string sRes = res.RawRes;

		}
	}
}
