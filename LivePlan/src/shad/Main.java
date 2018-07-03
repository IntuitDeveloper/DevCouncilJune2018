package shad;

import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import com.google.gson.JsonElement;
import com.google.gson.JsonParser;
import org.apache.log4j.xml.DOMConfigurator;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import javax.net.ssl.HttpsURLConnection;
import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.URL;
import java.nio.file.Files;
import java.nio.file.Paths;

public class Main {
    private static final Logger log = LoggerFactory.getLogger(Main.class);

    public static void main(String[] args) throws Exception {
        DOMConfigurator.configure("etc/log4j.xml");
        Main main = new Main();
        main.go();
    }


    private void go() throws Exception {
        String graphQLRequest = buildGraphQLRequest("Bills", null);
        log.info("graphQLRequest\n{}", graphQLRequest);
        String response = makeRequest(graphQLRequest);
        log.info("response\n{}", prettyPrint(response));
    }

    private String makeRequest(String queryString) throws Exception {
        URL url = new URL("https", "v4thirdparty-e2e.api.intuit.com", "/graphql");

        log.info("opening url {}", url.toString());
        HttpsURLConnection urlConnection = (HttpsURLConnection) url.openConnection();
        urlConnection.setDoOutput(true);
        urlConnection.setDoInput(true);
        urlConnection.setRequestMethod("POST"); //? Shouldn't this be a post?
        urlConnection.setRequestProperty("Content-type", "application/json");
        urlConnection.setRequestProperty("Accept", "*/*");
        urlConnection.setRequestProperty("Content-length", String.valueOf(queryString.length()));
        urlConnection.setRequestProperty("User-agent", "test-shad");
        urlConnection.setRequestProperty("Authorization", "Bearer eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiZGlyIn0..KljNIhZpyurOz74S2kAMSQ.HnH_flj5Ve5JYgG1Vq7MDsHVpuZFtCPhIMuuh37n4Qnsfw7B35i4W6Tt8NiNJ0vk-_0j8E8ZdQuHQkYVpaCbnPLQVfXEQJlV2gMaTslBnN-MC_TuQyi7ey1vXTVw9nh6CIFnfhXMkPgrWJvvQVLQ3v9CTDO-e6vyz6KORwRHpo27iTVO2fbPq1Xcs6q01LC_ohTLFeWpFTRabs20eYzALfE869KoXwy2wcINvx-C6NfhS3jst7Jl06m8oNPoB8cCtuM41ABi0f39jYd-1QYDEvJahPZYAu-fC7bPznZGPhE7SuRMglu5sD2rP9yWDZfzTSeURIy_-2quNSQx-fLvPElKr2mgSImcJvfSAGXq-7gchmj5Kr8F7zJ4KaaPuuRgnauRsMENoLSzo1WRMPSbO1YDUYDwHgBE_qGuqqd5SC482XnIHXHz-7E5Eey5_bxbYnkGJzbXE8-pzgXmn-i9UzMuY0U7R5s_-b5Yc0H_jJZnlfE2TrWng-vd0KxgY2L1a8eu6Xi3BSUr67QKUBk5L5iLf1ciQ95ppWtlfZd2xe7GByzytxqXfrt4Oz1tdAi7vfrwuIBkY-J8nT0vJsGvt-tnVYUwTIcLb1GWC8aHV0bOCLvuu19rwGn-5Nb035l_nqDg17i3kJ7RFdVTnG4v3UW6OiL_VM4ikKLSQT4yM1B2lNIBqGzeYHyde41-RGZj.EWAnzFpgYUdU7dCRkPx6xA");

        OutputStream outputStream = urlConnection.getOutputStream();
        DataOutputStream dataOutputStream = new DataOutputStream(outputStream);
        dataOutputStream.writeBytes(queryString);
        dataOutputStream.flush();
        dataOutputStream.close();
        outputStream.close();
        urlConnection.connect();

        int responseCode = urlConnection.getResponseCode();
        log.info("response code = {}", responseCode);

        InputStreamReader responseStreamReader = new InputStreamReader(urlConnection.getInputStream());
        BufferedReader bufferedResponseReader = new BufferedReader(responseStreamReader);
        StringBuilder responseStringBuffer = new StringBuilder();
        String line;
        while ((line = bufferedResponseReader.readLine()) != null) {
            responseStringBuffer.append(line);
        }
        return responseStringBuffer.toString();
    }


    private String buildGraphQLRequest(String name, String variables) throws Exception {
        String graphQL = loadGraphQL("graphql/" + name + ".graphql");
        StringBuilder sb = new StringBuilder();

        sb.append("{");

        sb.append("\"query\":\"")
            .append(graphQL)
            .append("\"");

        if (variables != null) {
            sb.append(", \"variables\":\"")
                .append(variables)
                .append("\"");
        }

        sb.append(", \"operationName\":\"")
            .append(name)
            .append("\"");

        sb.append("}");

        return sb.toString();
    }


    private String loadGraphQL(String path) throws Exception {
        byte[] encodedBytes = Files.readAllBytes(Paths.get(path));
        String fileContents = new String(encodedBytes);
        fileContents = fileContents
                .replace("\n", "\\n")
                .replace("\r", "\\r")
                .replace("\"", "\\\"");
        return fileContents;
    }



    private String prettyPrint(String jsonString) {
        Gson gson = new GsonBuilder().setPrettyPrinting().create();
        JsonParser jp = new JsonParser();
        JsonElement je = jp.parse(jsonString);
        return gson.toJson(je);
    }



}
