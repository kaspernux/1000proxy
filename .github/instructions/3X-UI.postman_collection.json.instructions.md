{
    "info": {
        "_postman_id": "dda3cab3-0e33-485f-96f9-d4262f437ac5",
        "name": "3X-UI",
        "description": "The **Postman collection** for the MHSanaei/3x-ui offers a comprehensive range of API endpoints, allowing users to manage various operations efficiently. These include authentication, inbound management (listing, retrieving details, updating, and deleting), and client operations (retrieving, updating, deleting, and resetting traffic). Additionally, the collection includes specialized tasks such as resetting traffic statistics, removing depleted clients, exporting the database, and generating backups. This collection is designed to simplify interaction with the MHSanaei/3x-ui API , making it easier to manage inbounds, clients, and other key functionalities.\n\n### Customization\n\nWhile the examples in this documentation primarily demonstrate the **vless** protocol, you can easily customize configurations and parameters to meet your specific needs.\n\nTo identify the exact data required for server interactions, use the **Google Chrome** browser to access your panel. Open the **Inspect** tool, go to the **Network** tab, and perform the desired action, such as creating an inbound or client. Examine the request payload to determine the necessary parameters for your configuration. You can then replace the body data in the Postman collection with your custom settings as needed.\n\n<img src=\"https://content.pstmn.io/178f072a-fe63-46d2-b016-fa14200d2359/Mjc3NjAxMTE1LTU3YWIxNTE0LTU1NTAtNDA2Yy1hYWNmLTU4OTQ2YTIxOGQ2MS5wbmc=\">\n\n[<img src=\"https://run.pstmn.io/button.svg\" alt=\"Run In Postman\">](https://god.gw.postman.com/run-collection/16802678-1a4c9270-ac77-40ed-959a-7aa56dc4a415?action=collection%2Ffork&source=rip_markdown&collection-url=entityId%3D16802678-1a4c9270-ac77-40ed-959a-7aa56dc4a415%26entityType%3Dcollection%26workspaceId%3D2cd38c01-c851-4a15-a972-f181c23359d9)",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
        "_exporter_id": "31233409",
        "_collection_link": "https://www.postman.com/hsanaei/3x-ui/collection/q1l5l0u/3x-ui?action=share&source=collection_link&creator=31233409"
    },
    "item": [
        {
            "name": "Login",
            "event": [
                {
                    "listen": "test",
                    "script": {
                        "exec": [""],
                        "type": "text/javascript",
                        "packages": {}
                    }
                }
            ],
            "protocolProfileBehavior": {
                "protocolVersion": "auto"
            },
            "request": {
                "method": "POST",
                "header": [],
                "body": {
                    "mode": "urlencoded",
                    "urlencoded": [
                        {
                            "key": "username",
                            "value": "{{USERNAME}}",
                            "type": "text"
                        },
                        {
                            "key": "password",
                            "value": "{{PASSWORD}}",
                            "type": "text"
                        }
                    ]
                },
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/login",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["login"]
                },
                "description": "## Description\n\nThis route is used to authenticate users and generate a session ID stored in a cookie named \"session,\" allowing authorized access to perform various operations within the panel.\n\n## Note\n\n- Retrieve the generated session ID from the cookie named \"session\".\n    \n- Ensure the provided credentials (`username` and `password`) are correct for successful authentication.\n    \n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Login",
                    "originalRequest": {
                        "method": "POST",
                        "header": [],
                        "body": {
                            "mode": "urlencoded",
                            "urlencoded": [
                                {
                                    "key": "username",
                                    "value": "{{USERNAME}}",
                                    "type": "text"
                                },
                                {
                                    "key": "password",
                                    "value": "{{PASSWORD}}",
                                    "type": "text"
                                }
                            ]
                        },
                        "url": {
                            "raw": "http://localhost:2053/login",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": ["login"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Set-Cookie",
                            "value": "3x-ui=MTczNTUyMzMyN3xEWDhFQVFMX2dBQUJFQUVRQUFCMV80QUFBUVp6ZEhKcGJtY01EQUFLVEU5SFNVNWZWVk5GVWhoNExYVnBMMlJoZEdGaVlYTmxMMjF2WkdWc0xsVnpaWExfZ1FNQkFRUlZjMlZ5QWYtQ0FBRUVBUUpKWkFFRUFBRUlWWE5sY201aGJXVUJEQUFCQ0ZCaGMzTjNiM0prQVF3QUFRdE1iMmRwYmxObFkzSmxkQUVNQUFBQUZQLUNFUUVDQVFWaFpHMXBiZ0VGWVdSdGFXNEF8e6Y2EKU4tk9sjoHdsA7Hb8TqYbZZclkP6EfZlCy1-bs=; Path=/; Expires=Mon, 30 Dec 2024 02:48:47 GMT; Max-Age=3600; HttpOnly"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Mon, 30 Dec 2024 01:48:47 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "74"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Login Successfully\",\n    \"obj\": null\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "POST",
                        "header": [],
                        "body": {
                            "mode": "urlencoded",
                            "urlencoded": [
                                {
                                    "key": "username",
                                    "value": "admin",
                                    "type": "text"
                                },
                                {
                                    "key": "password",
                                    "value": "admin",
                                    "type": "text"
                                }
                            ]
                        },
                        "url": {
                            "raw": "http://localhost:2053/login",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": ["login"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:36:08 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "96"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": false,\n    \"msg\": \"Invalid username or password or secret.\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Inbounds",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/list",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "list"]
                },
                "description": "## Description\n\nThis route is used to retrieve a comprehensive list of all inbounds along with their associated client options and statistics.\n\n## Note\n\n- Requires a valid session ID (from the login endpoint), Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- If no inbounds are available, the response will contain an empty array `[]`.\n    \n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Inbounds",
                    "originalRequest": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/list",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "list"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:47:40 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "1053"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": [\n        {\n            \"id\": 3,\n            \"up\": 0,\n            \"down\": 0,\n            \"total\": 0,\n            \"remark\": \"\",\n            \"enable\": true,\n            \"expiryTime\": 0,\n            \"clientStats\": [\n                {\n                    \"id\": 3,\n                    \"inboundId\": 3,\n                    \"enable\": true,\n                    \"email\": \"hyvcs325\",\n                    \"up\": 0,\n                    \"down\": 0,\n                    \"expiryTime\": 0,\n                    \"total\": 0,\n                    \"reset\": 0\n                },\n                {\n                    \"id\": 5,\n                    \"inboundId\": 3,\n                    \"enable\": true,\n                    \"email\": \"27225ost\",\n                    \"up\": 0,\n                    \"down\": 0,\n                    \"expiryTime\": 0,\n                    \"total\": 0,\n                    \"reset\": 0\n                }\n            ],\n            \"listen\": \"\",\n            \"port\": 37155,\n            \"protocol\": \"vless\",\n            \"settings\": \"{\\n  \\\"clients\\\": [\\n    {\\n      \\\"email\\\": \\\"hyvcs325\\\",\\n      \\\"enable\\\": true,\\n      \\\"expiryTime\\\": 0,\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"id\\\": \\\"819920c0-22c8-4c83-8713-9c3da4980396\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"reset\\\": 0,\\n      \\\"subId\\\": \\\"jmrwimzhicxm7hrm\\\",\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"totalGB\\\": 0\\n    },\\n    {\\n      \\\"email\\\": \\\"27225ost\\\",\\n      \\\"enable\\\": true,\\n      \\\"expiryTime\\\": 0,\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"id\\\": \\\"bf036995-a81d-41b3-8e06-8e233418c96a\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"reset\\\": 0,\\n      \\\"subId\\\": \\\"jw45dtw6rhvefikz\\\",\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"totalGB\\\": 0\\n    }\\n  ],\\n  \\\"decryption\\\": \\\"none\\\",\\n  \\\"fallbacks\\\": []\\n}\",\n            \"streamSettings\": \"{\\n  \\\"network\\\": \\\"tcp\\\",\\n  \\\"security\\\": \\\"reality\\\",\\n  \\\"externalProxy\\\": [],\\n  \\\"realitySettings\\\": {\\n    \\\"show\\\": false,\\n    \\\"xver\\\": 0,\\n    \\\"dest\\\": \\\"yahoo.com:443\\\",\\n    \\\"serverNames\\\": [\\n      \\\"yahoo.com\\\",\\n      \\\"www.yahoo.com\\\"\\n    ],\\n    \\\"privateKey\\\": \\\"QJS9AerMmDU-DrTe_SAL7vX6_2wg19OxCuthZLLs40g\\\",\\n    \\\"minClient\\\": \\\"\\\",\\n    \\\"maxClient\\\": \\\"\\\",\\n    \\\"maxTimediff\\\": 0,\\n    \\\"shortIds\\\": [\\n      \\\"97de\\\",\\n      \\\"5f7b4df7d0605151\\\",\\n      \\\"cc1a7d15c439\\\",\\n      \\\"f196851a\\\",\\n      \\\"e291c2\\\",\\n      \\\"b10c0deeceec08\\\",\\n      \\\"19\\\",\\n      \\\"7db6c63a5d\\\"\\n    ],\\n    \\\"settings\\\": {\\n      \\\"publicKey\\\": \\\"UNXIILQ_LpbZdXGbhNCMele1gaPVIfCJ9N0AoLYdRUE\\\",\\n      \\\"fingerprint\\\": \\\"random\\\",\\n      \\\"serverName\\\": \\\"\\\",\\n      \\\"spiderX\\\": \\\"/\\\"\\n    }\\n  },\\n  \\\"tcpSettings\\\": {\\n    \\\"acceptProxyProtocol\\\": false,\\n    \\\"header\\\": {\\n      \\\"type\\\": \\\"none\\\"\\n    }\\n  }\\n}\",\n            \"tag\": \"inbound-37155\",\n            \"sniffing\": \"{\\n  \\\"enabled\\\": false,\\n  \\\"destOverride\\\": [\\n    \\\"http\\\",\\n    \\\"tls\\\",\\n    \\\"quic\\\",\\n    \\\"fakedns\\\"\\n  ],\\n  \\\"metadataOnly\\\": false,\\n  \\\"routeOnly\\\": false\\n}\",\n            \"allocate\": \"{\\n  \\\"strategy\\\": \\\"always\\\",\\n  \\\"refresh\\\": 5,\\n  \\\"concurrency\\\": 3\\n}\"\n        },\n        {\n            \"id\": 4,\n            \"up\": 0,\n            \"down\": 0,\n            \"total\": 0,\n            \"remark\": \"\",\n            \"enable\": true,\n            \"expiryTime\": 0,\n            \"clientStats\": [\n                {\n                    \"id\": 4,\n                    \"inboundId\": 4,\n                    \"enable\": true,\n                    \"email\": \"s729v2km\",\n                    \"up\": 0,\n                    \"down\": 0,\n                    \"expiryTime\": 0,\n                    \"total\": 0,\n                    \"reset\": 0\n                }\n            ],\n            \"listen\": \"\",\n            \"port\": 44360,\n            \"protocol\": \"vless\",\n            \"settings\": \"{\\n  \\\"clients\\\": [\\n    {\\n      \\\"id\\\": \\\"a39c9655-bcbb-43c4-9b3b-ebd8b7ae9e1e\\\",\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"email\\\": \\\"s729v2km\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"totalGB\\\": 0,\\n      \\\"expiryTime\\\": 0,\\n      \\\"enable\\\": true,\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"subId\\\": \\\"n2b9ubaioe06cak8\\\",\\n      \\\"reset\\\": 0\\n    }\\n  ],\\n  \\\"decryption\\\": \\\"none\\\",\\n  \\\"fallbacks\\\": []\\n}\",\n            \"streamSettings\": \"{\\n  \\\"network\\\": \\\"tcp\\\",\\n  \\\"security\\\": \\\"none\\\",\\n  \\\"externalProxy\\\": [],\\n  \\\"tcpSettings\\\": {\\n    \\\"acceptProxyProtocol\\\": false,\\n    \\\"header\\\": {\\n      \\\"type\\\": \\\"none\\\"\\n    }\\n  }\\n}\",\n            \"tag\": \"inbound-44360\",\n            \"sniffing\": \"{\\n  \\\"enabled\\\": false,\\n  \\\"destOverride\\\": [\\n    \\\"http\\\",\\n    \\\"tls\\\",\\n    \\\"quic\\\",\\n    \\\"fakedns\\\"\\n  ],\\n  \\\"metadataOnly\\\": false,\\n  \\\"routeOnly\\\": false\\n}\",\n            \"allocate\": \"{\\n  \\\"strategy\\\": \\\"always\\\",\\n  \\\"refresh\\\": 5,\\n  \\\"concurrency\\\": 3\\n}\"\n        }\n    ]\n}"
                },
                {
                    "name": "No Inbounds",
                    "originalRequest": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/list",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "list"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:46:23 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "58"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": []\n}"
                }
            ]
        },
        {
            "name": "Inbound",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/get/{inboundId}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "get", "{inboundId}"]
                },
                "description": "## Description\n\nThis route is used to retrieve statistics and details for a specific inbound connection identified by `{inboundId}`. This includes information about the inbound itself, its statistics, and the clients connected to it.\n\n## **Path Parameter**\n\n- `{inboundId}`: Identifier of the specific inbound for which information is requested.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint), Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Ensure that the provided `{inboundId}` corresponds to an existing inbound within the system.\n    \n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/get/15",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "get", "15"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:45:04 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "827"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": {\n        \"id\": 2,\n        \"up\": 0,\n        \"down\": 0,\n        \"total\": 0,\n        \"remark\": \"\",\n        \"enable\": true,\n        \"expiryTime\": 0,\n        \"clientStats\": null,\n        \"listen\": \"\",\n        \"port\": 38476,\n        \"protocol\": \"vless\",\n        \"settings\": \"{\\n  \\\"clients\\\": [\\n    {\\n      \\\"id\\\": \\\"7da4dd82-66e6-4dfa-a66b-bf423f5407ea\\\",\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"email\\\": \\\"t6l5ljc9\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"totalGB\\\": 0,\\n      \\\"expiryTime\\\": 0,\\n      \\\"enable\\\": true,\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"subId\\\": \\\"ile0ixxgdmjeuz5m\\\",\\n      \\\"reset\\\": 0\\n    }\\n  ],\\n  \\\"decryption\\\": \\\"none\\\",\\n  \\\"fallbacks\\\": []\\n}\",\n        \"streamSettings\": \"{\\n  \\\"network\\\": \\\"tcp\\\",\\n  \\\"security\\\": \\\"reality\\\",\\n  \\\"externalProxy\\\": [],\\n  \\\"realitySettings\\\": {\\n    \\\"show\\\": false,\\n    \\\"xver\\\": 0,\\n    \\\"dest\\\": \\\"yahoo.com:443\\\",\\n    \\\"serverNames\\\": [\\n      \\\"yahoo.com\\\",\\n      \\\"www.yahoo.com\\\"\\n    ],\\n    \\\"privateKey\\\": \\\"yKUjT7SgfQH8fOTqsKLhwkOWiqRi5oC0Y4lFZXb0CTE\\\",\\n    \\\"minClient\\\": \\\"\\\",\\n    \\\"maxClient\\\": \\\"\\\",\\n    \\\"maxTimediff\\\": 0,\\n    \\\"shortIds\\\": [\\n      \\\"8714e8f78bd9\\\",\\n      \\\"8692\\\",\\n      \\\"4e\\\",\\n      \\\"9c46e1\\\",\\n      \\\"52c0f48e\\\",\\n      \\\"2d439ce7fd35bd\\\",\\n      \\\"a64d2fc4a1\\\",\\n      \\\"2520ce66461ba14d\\\"\\n    ],\\n    \\\"settings\\\": {\\n      \\\"publicKey\\\": \\\"HBOoWQWTTFlN1CyPL-wzf-0S28Ae7D4E23f6GL9FaXw\\\",\\n      \\\"fingerprint\\\": \\\"random\\\",\\n      \\\"serverName\\\": \\\"\\\",\\n      \\\"spiderX\\\": \\\"/\\\"\\n    }\\n  },\\n  \\\"tcpSettings\\\": {\\n    \\\"acceptProxyProtocol\\\": false,\\n    \\\"header\\\": {\\n      \\\"type\\\": \\\"none\\\"\\n    }\\n  }\\n}\",\n        \"tag\": \"inbound-38476\",\n        \"sniffing\": \"{\\n  \\\"enabled\\\": false,\\n  \\\"destOverride\\\": [\\n    \\\"http\\\",\\n    \\\"tls\\\",\\n    \\\"quic\\\",\\n    \\\"fakedns\\\"\\n  ],\\n  \\\"metadataOnly\\\": false,\\n  \\\"routeOnly\\\": false\\n}\",\n        \"allocate\": \"{\\n  \\\"strategy\\\": \\\"always\\\",\\n  \\\"refresh\\\": 5,\\n  \\\"concurrency\\\": 3\\n}\"\n    }\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/get/1",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "get", "1"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:45:50 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "90"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": false,\n    \"msg\": \"Obtain Failed: record not found\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Client Traffics with email",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/getClientTraffics/{email}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "getClientTraffics",
                        "{email}"
                    ]
                },
                "description": "## Description\n\nThis route is used to retrieve information about a specific client based on their email. This endpoint provides details such as traffic statistics and other relevant information related to the client.\n\n## **Path Parameter**\n\n- `{email}`: Email address of the client for whom information is requested.\n    \n\n## **Note**\n\n- Requires a valid session ID (from the login endpoint), Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Ensure that the provided `{email}` corresponds to a valid client in the system to retrieve accurate information.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/getClientTraffics/s729v2km",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "getClientTraffics",
                                "s729v2km"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:48:52 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "132"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": {\n        \"id\": 4,\n        \"inboundId\": 4,\n        \"enable\": true,\n        \"email\": \"s729v2km\",\n        \"up\": 0,\n        \"down\": 0,\n        \"expiryTime\": 0,\n        \"total\": 0,\n        \"reset\": 0\n    }\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/getClientTraffics/s729v2",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "getClientTraffics",
                                "s729v2"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:49:06 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "60"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Client Traffics with ID",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/getClientTrafficsById/{uuid}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "getClientTrafficsById",
                        "{uuid}"
                    ]
                },
                "description": "## Description\n\nThis route is used to retrieve information about a specific client based on their email. This endpoint provides details such as traffic statistics and other relevant information related to the client.\n\n## **Path Parameter**\n\n- `{email}`: Email address of the client for whom information is requested.\n    \n\n## **Note**\n\n- Requires a valid session ID (from the login endpoint), Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Ensure that the provided `{email}` corresponds to a valid client in the system to retrieve accurate information.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/getClientTrafficsById/a39c9655-bcbb-43c4-9b3b-ebd8b7ae9e1e",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "getClientTrafficsById",
                                "a39c9655-bcbb-43c4-9b3b-ebd8b7ae9e1e"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:50:48 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "136"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": [\n        {\n            \"id\": 4,\n            \"inboundId\": 4,\n            \"enable\": true,\n            \"email\": \"s729v2km\",\n            \"up\": 0,\n            \"down\": 0,\n            \"expiryTime\": 0,\n            \"total\": 0,\n            \"reset\": 0\n        }\n    ]\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/getClientTrafficsById/a39c9655-bcbb-43c4-9b3b-ebd8b7ae9111",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "getClientTrafficsById",
                                "a39c9655-bcbb-43c4-9b3b-ebd8b7ae9111"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:51:10 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "58"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": []\n}"
                }
            ]
        },
        {
            "name": "tgbot - sends backup to admins",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "",
                        "value": "",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/createbackup",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "createbackup"]
                },
                "description": "## Description\n\nThis endpoint triggers the creation of a system backup and initiates the delivery of the backup file to designated administrators via a configured Telegram bot. The server verifies the Telegram bot's activation status within the system settings and checks for the presence of admin IDs specified in the settings before sending the backup.\n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Upon implementation, the backup file might be sent through the Telegram bot registered in the panel settings.\n    \n- Handle any potential errors or failure messages returned in the response."
            },
            "response": []
        },
        {
            "name": "Client Ip address",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/clientIps/{email}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "clientIps", "{email}"]
                },
                "description": "## Description\n\nThis route is used to retrieve the IP records associated with a specific client identified by their email.\n\n## Path Parameter\n\n- **`{email}`** : Email address of the client for whom IP records are requested.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint), Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Ensure that the provided `{email}` corresponds to a valid client in the system to retrieve accurate IP records.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Response",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/clientIps/s729v2km",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "clientIps",
                                "s729v2km"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:53:11 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "70"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": \"No IP Record\"\n}"
                }
            ]
        },
        {
            "name": "Add Inbound",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\r\n\"up\": 0,\r\n\"down\": 0,\r\n\"total\": 0,\r\n\"remark\": \"New\",\r\n\"enable\": true,\r\n\"expiryTime\": 0,\r\n\"listen\": \"\",\r\n\"port\": 55421,\r\n\"protocol\": \"vless\",\r\n\"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"b86c0cdc-8a02-4da4-8693-72ba27005587\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"nt3wz904\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 0,\\\"expiryTime\\\": 0,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"rqv5zw1ydutamcp0\\\",\\\"reset\\\": 0}],\\\"decryption\\\": \\\"none\\\",\\\"fallbacks\\\": []}\",\r\n\"streamSettings\": \"{\\\"network\\\": \\\"tcp\\\",\\\"security\\\": \\\"reality\\\",\\\"externalProxy\\\": [],\\\"realitySettings\\\": {\\\"show\\\": false,\\\"xver\\\": 0,\\\"dest\\\": \\\"yahoo.com:443\\\",\\\"serverNames\\\": [\\\"yahoo.com\\\",\\\"www.yahoo.com\\\"],\\\"privateKey\\\": \\\"wIc7zBUiTXBGxM7S7wl0nCZ663OAvzTDNqS7-bsxV3A\\\",\\\"minClient\\\": \\\"\\\",\\\"maxClient\\\": \\\"\\\",\\\"maxTimediff\\\": 0,\\\"shortIds\\\": [\\\"47595474\\\",\\\"7a5e30\\\",\\\"810c1efd750030e8\\\",\\\"99\\\",\\\"9c19c134b8\\\",\\\"35fd\\\",\\\"2409c639a707b4\\\",\\\"c98fc6b39f45\\\"],\\\"settings\\\": {\\\"publicKey\\\": \\\"2UqLjQFhlvLcY7VzaKRotIDQFOgAJe1dYD1njigp9wk\\\",\\\"fingerprint\\\": \\\"random\\\",\\\"serverName\\\": \\\"\\\",\\\"spiderX\\\": \\\"/\\\"}},\\\"tcpSettings\\\": {\\\"acceptProxyProtocol\\\": false,\\\"header\\\": {\\\"type\\\": \\\"none\\\"}}}\",\r\n\"sniffing\": \"{\\\"enabled\\\": true,\\\"destOverride\\\": [\\\"http\\\",\\\"tls\\\",\\\"quic\\\",\\\"fakedns\\\"],\\\"metadataOnly\\\": false,\\\"routeOnly\\\": false}\",\r\n\"allocate\": \"{\\\"strategy\\\": \\\"always\\\",\\\"refresh\\\": 5,\\\"concurrency\\\": 3}\"\r\n}",
                    "options": {
                        "raw": {
                            "language": "json"
                        }
                    }
                },
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/add",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "add"]
                },
                "description": "## Description\n\nThis route is used to add a new inbound configuration.\n\n## Note\n\n- Requires a valid session ID (from the login endpoint), Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Ensure the provided inbound configuration parameters are correct to add the inbound successfully.\n- Ensure that sub-arrays or objects within the JSON body are stringified in JSON format for correct parsing by the panel.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\r\n\"up\": 0,\r\n\"down\": 0,\r\n\"total\": 0,\r\n\"remark\": \"New\",\r\n\"enable\": true,\r\n\"expiryTime\": 0,\r\n\"listen\": \"\",\r\n\"port\": 55421,\r\n\"protocol\": \"vless\",\r\n\"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"b86c0cdc-8a02-4da4-8693-72ba27005587\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"nt3wz904\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 0,\\\"expiryTime\\\": 0,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"rqv5zw1ydutamcp0\\\",\\\"reset\\\": 0}],\\\"decryption\\\": \\\"none\\\",\\\"fallbacks\\\": []}\",\r\n\"streamSettings\": \"{\\\"network\\\": \\\"tcp\\\",\\\"security\\\": \\\"reality\\\",\\\"externalProxy\\\": [],\\\"realitySettings\\\": {\\\"show\\\": false,\\\"xver\\\": 0,\\\"dest\\\": \\\"yahoo.com:443\\\",\\\"serverNames\\\": [\\\"yahoo.com\\\",\\\"www.yahoo.com\\\"],\\\"privateKey\\\": \\\"wIc7zBUiTXBGxM7S7wl0nCZ663OAvzTDNqS7-bsxV3A\\\",\\\"minClient\\\": \\\"\\\",\\\"maxClient\\\": \\\"\\\",\\\"maxTimediff\\\": 0,\\\"shortIds\\\": [\\\"47595474\\\",\\\"7a5e30\\\",\\\"810c1efd750030e8\\\",\\\"99\\\",\\\"9c19c134b8\\\",\\\"35fd\\\",\\\"2409c639a707b4\\\",\\\"c98fc6b39f45\\\"],\\\"settings\\\": {\\\"publicKey\\\": \\\"2UqLjQFhlvLcY7VzaKRotIDQFOgAJe1dYD1njigp9wk\\\",\\\"fingerprint\\\": \\\"random\\\",\\\"serverName\\\": \\\"\\\",\\\"spiderX\\\": \\\"/\\\"}},\\\"tcpSettings\\\": {\\\"acceptProxyProtocol\\\": false,\\\"header\\\": {\\\"type\\\": \\\"none\\\"}}}\",\r\n\"sniffing\": \"{\\\"enabled\\\": true,\\\"destOverride\\\": [\\\"http\\\",\\\"tls\\\",\\\"quic\\\",\\\"fakedns\\\"],\\\"metadataOnly\\\": false,\\\"routeOnly\\\": false}\",\r\n\"allocate\": \"{\\\"strategy\\\": \\\"always\\\",\\\"refresh\\\": 5,\\\"concurrency\\\": 3}\"\r\n}",
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/add",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "add"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:54:17 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "791"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Create Successfully\",\n    \"obj\": {\n        \"id\": 5,\n        \"up\": 0,\n        \"down\": 0,\n        \"total\": 0,\n        \"remark\": \"New\",\n        \"enable\": true,\n        \"expiryTime\": 0,\n        \"clientStats\": null,\n        \"listen\": \"\",\n        \"port\": 55421,\n        \"protocol\": \"vless\",\n        \"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"b86c0cdc-8a02-4da4-8693-72ba27005587\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"nt3wz904\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 0,\\\"expiryTime\\\": 0,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"rqv5zw1ydutamcp0\\\",\\\"reset\\\": 0}],\\\"decryption\\\": \\\"none\\\",\\\"fallbacks\\\": []}\",\n        \"streamSettings\": \"{\\\"network\\\": \\\"tcp\\\",\\\"security\\\": \\\"reality\\\",\\\"externalProxy\\\": [],\\\"realitySettings\\\": {\\\"show\\\": false,\\\"xver\\\": 0,\\\"dest\\\": \\\"yahoo.com:443\\\",\\\"serverNames\\\": [\\\"yahoo.com\\\",\\\"www.yahoo.com\\\"],\\\"privateKey\\\": \\\"wIc7zBUiTXBGxM7S7wl0nCZ663OAvzTDNqS7-bsxV3A\\\",\\\"minClient\\\": \\\"\\\",\\\"maxClient\\\": \\\"\\\",\\\"maxTimediff\\\": 0,\\\"shortIds\\\": [\\\"47595474\\\",\\\"7a5e30\\\",\\\"810c1efd750030e8\\\",\\\"99\\\",\\\"9c19c134b8\\\",\\\"35fd\\\",\\\"2409c639a707b4\\\",\\\"c98fc6b39f45\\\"],\\\"settings\\\": {\\\"publicKey\\\": \\\"2UqLjQFhlvLcY7VzaKRotIDQFOgAJe1dYD1njigp9wk\\\",\\\"fingerprint\\\": \\\"random\\\",\\\"serverName\\\": \\\"\\\",\\\"spiderX\\\": \\\"/\\\"}},\\\"tcpSettings\\\": {\\\"acceptProxyProtocol\\\": false,\\\"header\\\": {\\\"type\\\": \\\"none\\\"}}}\",\n        \"tag\": \"inbound-55421\",\n        \"sniffing\": \"{\\\"enabled\\\": true,\\\"destOverride\\\": [\\\"http\\\",\\\"tls\\\",\\\"quic\\\",\\\"fakedns\\\"],\\\"metadataOnly\\\": false,\\\"routeOnly\\\": false}\",\n        \"allocate\": \"{\\\"strategy\\\": \\\"always\\\",\\\"refresh\\\": 5,\\\"concurrency\\\": 3}\"\n    }\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\r\n\"up\": 0,\r\n\"down\": 0,\r\n\"total\": 0,\r\n\"remark\": \"New\",\r\n\"enable\": true,\r\n\"expiryTime\": 0,\r\n\"listen\": \"\",\r\n\"port\": 55421,\r\n\"protocol\": \"vless\",\r\n\"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"b86c0cdc-8a02-4da4-8693-72ba27005587\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"nt3wz904\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 0,\\\"expiryTime\\\": 0,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"rqv5zw1ydutamcp0\\\",\\\"reset\\\": 0}],\\\"decryption\\\": \\\"none\\\",\\\"fallbacks\\\": []}\",\r\n\"streamSettings\": \"{\\\"network\\\": \\\"tcp\\\",\\\"security\\\": \\\"reality\\\",\\\"externalProxy\\\": [],\\\"realitySettings\\\": {\\\"show\\\": false,\\\"xver\\\": 0,\\\"dest\\\": \\\"yahoo.com:443\\\",\\\"serverNames\\\": [\\\"yahoo.com\\\",\\\"www.yahoo.com\\\"],\\\"privateKey\\\": \\\"wIc7zBUiTXBGxM7S7wl0nCZ663OAvzTDNqS7-bsxV3A\\\",\\\"minClient\\\": \\\"\\\",\\\"maxClient\\\": \\\"\\\",\\\"maxTimediff\\\": 0,\\\"shortIds\\\": [\\\"47595474\\\",\\\"7a5e30\\\",\\\"810c1efd750030e8\\\",\\\"99\\\",\\\"9c19c134b8\\\",\\\"35fd\\\",\\\"2409c639a707b4\\\",\\\"c98fc6b39f45\\\"],\\\"settings\\\": {\\\"publicKey\\\": \\\"2UqLjQFhlvLcY7VzaKRotIDQFOgAJe1dYD1njigp9wk\\\",\\\"fingerprint\\\": \\\"random\\\",\\\"serverName\\\": \\\"\\\",\\\"spiderX\\\": \\\"/\\\"}},\\\"tcpSettings\\\": {\\\"acceptProxyProtocol\\\": false,\\\"header\\\": {\\\"type\\\": \\\"none\\\"}}}\",\r\n\"sniffing\": \"{\\\"enabled\\\": true,\\\"destOverride\\\": [\\\"http\\\",\\\"tls\\\",\\\"quic\\\",\\\"fakedns\\\"],\\\"metadataOnly\\\": false,\\\"routeOnly\\\": false}\",\r\n\"allocate\": \"{\\\"strategy\\\": \\\"always\\\",\\\"refresh\\\": 5,\\\"concurrency\\\": 3}\"\r\n}",
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/add",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "add"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 13:54:54 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "809"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": false,\n    \"msg\": \"Create Failed: Port already exists: 55421\\n\",\n    \"obj\": {\n        \"id\": 0,\n        \"up\": 0,\n        \"down\": 0,\n        \"total\": 0,\n        \"remark\": \"New\",\n        \"enable\": true,\n        \"expiryTime\": 0,\n        \"clientStats\": null,\n        \"listen\": \"\",\n        \"port\": 55421,\n        \"protocol\": \"vless\",\n        \"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"b86c0cdc-8a02-4da4-8693-72ba27005587\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"nt3wz904\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 0,\\\"expiryTime\\\": 0,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"rqv5zw1ydutamcp0\\\",\\\"reset\\\": 0}],\\\"decryption\\\": \\\"none\\\",\\\"fallbacks\\\": []}\",\n        \"streamSettings\": \"{\\\"network\\\": \\\"tcp\\\",\\\"security\\\": \\\"reality\\\",\\\"externalProxy\\\": [],\\\"realitySettings\\\": {\\\"show\\\": false,\\\"xver\\\": 0,\\\"dest\\\": \\\"yahoo.com:443\\\",\\\"serverNames\\\": [\\\"yahoo.com\\\",\\\"www.yahoo.com\\\"],\\\"privateKey\\\": \\\"wIc7zBUiTXBGxM7S7wl0nCZ663OAvzTDNqS7-bsxV3A\\\",\\\"minClient\\\": \\\"\\\",\\\"maxClient\\\": \\\"\\\",\\\"maxTimediff\\\": 0,\\\"shortIds\\\": [\\\"47595474\\\",\\\"7a5e30\\\",\\\"810c1efd750030e8\\\",\\\"99\\\",\\\"9c19c134b8\\\",\\\"35fd\\\",\\\"2409c639a707b4\\\",\\\"c98fc6b39f45\\\"],\\\"settings\\\": {\\\"publicKey\\\": \\\"2UqLjQFhlvLcY7VzaKRotIDQFOgAJe1dYD1njigp9wk\\\",\\\"fingerprint\\\": \\\"random\\\",\\\"serverName\\\": \\\"\\\",\\\"spiderX\\\": \\\"/\\\"}},\\\"tcpSettings\\\": {\\\"acceptProxyProtocol\\\": false,\\\"header\\\": {\\\"type\\\": \\\"none\\\"}}}\",\n        \"tag\": \"inbound-55421\",\n        \"sniffing\": \"{\\\"enabled\\\": true,\\\"destOverride\\\": [\\\"http\\\",\\\"tls\\\",\\\"quic\\\",\\\"fakedns\\\"],\\\"metadataOnly\\\": false,\\\"routeOnly\\\": false}\",\n        \"allocate\": \"{\\\"strategy\\\": \\\"always\\\",\\\"refresh\\\": 5,\\\"concurrency\\\": 3}\"\n    }\n}"
                }
            ]
        },
        {
            "name": "Add Client to inbound",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\r\n\t\"id\": 5,\r\n\t\"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"bbfad557-28f2-47e5-9f3d-e3c7f532fbda\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"dp1plmlt8\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 0,\\\"expiryTime\\\": 0,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"2rv0gb458kbfl532\\\",\\\"reset\\\": 0}]}\"\r\n}",
                    "options": {
                        "raw": {
                            "language": "json"
                        }
                    }
                },
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/addClient",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "addClient"]
                },
                "description": "## Description\n\nThis route is used to add a new client to a specific inbound identified by its ID.\n\n## Note\n\n- Requires a valid session ID (from the login endpoint), Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Verify that the provided inbound ID (`id`) corresponds to an existing inbound within the system.\n- Format the client information in the `settings` parameter as a stringified JSON format within the request body.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\r\n\t\"id\": 5,\r\n\t\"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"bbfad557-28f2-47e5-9f3d-e3c7f532fbda\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"dp1plmlt8\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 0,\\\"expiryTime\\\": 0,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"2rv0gb458kbfl532\\\",\\\"reset\\\": 0}]}\"\r\n}",
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/addClient",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "addClient"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:02:57 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "84"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Client(s) added Successfully\",\n    \"obj\": null\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\r\n\t\"id\": 5,\r\n\t\"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"bbfad557-28f2-47e5-9f3d-e3c7f532fbda\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"dp1plmlt8\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 0,\\\"expiryTime\\\": 0,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"2rv0gb458kbfl532\\\",\\\"reset\\\": 0}]}\"\r\n}",
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/addClient",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "addClient"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:03:18 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "112"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": false,\n    \"msg\": \"Something went wrong! Failed: Duplicate email: dp1plmlt8\\n\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Update Inbound",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\r\n\t\"up\": 0,\r\n\t\"down\": 0,\r\n\t\"total\": 0,\r\n\t\"remark\": \"\",\r\n\t\"enable\": true,\r\n\t\"expiryTime\": 0,\r\n\t\"listen\": \"\",\r\n\t\"port\": 44360,\r\n\t\"protocol\": \"vless\",\r\n\t\"settings\": \"{\\n  \\\"clients\\\": [\\n    {\\n      \\\"id\\\": \\\"a39c9655-bcbb-43c4-9b3b-ebd8b7ae9e1e\\\",\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"email\\\": \\\"s729v2km\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"totalGB\\\": 0,\\n      \\\"expiryTime\\\": 0,\\n      \\\"enable\\\": true,\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"subId\\\": \\\"n2b9ubaioe06cak8\\\",\\n      \\\"reset\\\": 0\\n    }\\n  ],\\n  \\\"decryption\\\": \\\"none\\\",\\n  \\\"fallbacks\\\": []\\n}\",\r\n\t\"streamSettings\": \"{\\n  \\\"network\\\": \\\"tcp\\\",\\n  \\\"security\\\": \\\"none\\\",\\n  \\\"externalProxy\\\": [],\\n  \\\"tcpSettings\\\": {\\n    \\\"acceptProxyProtocol\\\": false,\\n    \\\"header\\\": {\\n      \\\"type\\\": \\\"none\\\"\\n    }\\n  }\\n}\",\r\n\t\"sniffing\": \"{\\n  \\\"enabled\\\": false,\\n  \\\"destOverride\\\": [\\n    \\\"http\\\",\\n    \\\"tls\\\",\\n    \\\"quic\\\",\\n    \\\"fakedns\\\"\\n  ],\\n  \\\"metadataOnly\\\": false,\\n  \\\"routeOnly\\\": false\\n}\",\r\n\t\"allocate\": \"{\\n  \\\"strategy\\\": \\\"always\\\",\\n  \\\"refresh\\\": 5,\\n  \\\"concurrency\\\": 3\\n}\"\r\n}",
                    "options": {
                        "raw": {
                            "language": "json"
                        }
                    }
                },
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/update/{inboundId}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "update",
                        "{inboundId}"
                    ]
                },
                "description": "## Description\n\nThis route is used to update an existing inbound identified by its ID (`{inboundId}`).\n\n## **Path Parameter**\n\n- `{inboundId}`: Identifier of the specific inbound to be updated.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint), Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Verify that the provided `{inboundId}` corresponds to an existing inbound within the system.\n- Ensure that sub-arrays or objects within the JSON body are stringified in JSON format for correct parsing by the panel.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\r\n\t\"up\": 0,\r\n\t\"down\": 0,\r\n\t\"total\": 0,\r\n\t\"remark\": \"\",\r\n\t\"enable\": true,\r\n\t\"expiryTime\": 0,\r\n\t\"listen\": \"\",\r\n\t\"port\": 44360,\r\n\t\"protocol\": \"vless\",\r\n\t\"settings\": \"{\\n  \\\"clients\\\": [\\n    {\\n      \\\"id\\\": \\\"a39c9655-bcbb-43c4-9b3b-ebd8b7ae9e1e\\\",\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"email\\\": \\\"s729v2km\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"totalGB\\\": 0,\\n      \\\"expiryTime\\\": 0,\\n      \\\"enable\\\": true,\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"subId\\\": \\\"n2b9ubaioe06cak8\\\",\\n      \\\"reset\\\": 0\\n    }\\n  ],\\n  \\\"decryption\\\": \\\"none\\\",\\n  \\\"fallbacks\\\": []\\n}\",\r\n\t\"streamSettings\": \"{\\n  \\\"network\\\": \\\"tcp\\\",\\n  \\\"security\\\": \\\"none\\\",\\n  \\\"externalProxy\\\": [],\\n  \\\"tcpSettings\\\": {\\n    \\\"acceptProxyProtocol\\\": false,\\n    \\\"header\\\": {\\n      \\\"type\\\": \\\"none\\\"\\n    }\\n  }\\n}\",\r\n\t\"sniffing\": \"{\\n  \\\"enabled\\\": false,\\n  \\\"destOverride\\\": [\\n    \\\"http\\\",\\n    \\\"tls\\\",\\n    \\\"quic\\\",\\n    \\\"fakedns\\\"\\n  ],\\n  \\\"metadataOnly\\\": false,\\n  \\\"routeOnly\\\": false\\n}\",\r\n\t\"allocate\": \"{\\n  \\\"strategy\\\": \\\"always\\\",\\n  \\\"refresh\\\": 5,\\n  \\\"concurrency\\\": 3\\n}\"\r\n}",
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/update/4",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "update", "4"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:08:09 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "531"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Update Successfully\",\n    \"obj\": {\n        \"id\": 4,\n        \"up\": 0,\n        \"down\": 0,\n        \"total\": 0,\n        \"remark\": \"\",\n        \"enable\": true,\n        \"expiryTime\": 0,\n        \"clientStats\": null,\n        \"listen\": \"\",\n        \"port\": 44360,\n        \"protocol\": \"vless\",\n        \"settings\": \"{\\n  \\\"clients\\\": [\\n    {\\n      \\\"id\\\": \\\"a39c9655-bcbb-43c4-9b3b-ebd8b7ae9e1e\\\",\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"email\\\": \\\"s729v2km\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"totalGB\\\": 0,\\n      \\\"expiryTime\\\": 0,\\n      \\\"enable\\\": true,\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"subId\\\": \\\"n2b9ubaioe06cak8\\\",\\n      \\\"reset\\\": 0\\n    }\\n  ],\\n  \\\"decryption\\\": \\\"none\\\",\\n  \\\"fallbacks\\\": []\\n}\",\n        \"streamSettings\": \"{\\n  \\\"network\\\": \\\"tcp\\\",\\n  \\\"security\\\": \\\"none\\\",\\n  \\\"externalProxy\\\": [],\\n  \\\"tcpSettings\\\": {\\n    \\\"acceptProxyProtocol\\\": false,\\n    \\\"header\\\": {\\n      \\\"type\\\": \\\"none\\\"\\n    }\\n  }\\n}\",\n        \"tag\": \"\",\n        \"sniffing\": \"{\\n  \\\"enabled\\\": false,\\n  \\\"destOverride\\\": [\\n    \\\"http\\\",\\n    \\\"tls\\\",\\n    \\\"quic\\\",\\n    \\\"fakedns\\\"\\n  ],\\n  \\\"metadataOnly\\\": false,\\n  \\\"routeOnly\\\": false\\n}\",\n        \"allocate\": \"{\\n  \\\"strategy\\\": \\\"always\\\",\\n  \\\"refresh\\\": 5,\\n  \\\"concurrency\\\": 3\\n}\"\n    }\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\r\n\t\"up\": 0,\r\n\t\"down\": 0,\r\n\t\"total\": 0,\r\n\t\"remark\": \"\",\r\n\t\"enable\": true,\r\n\t\"expiryTime\": 0,\r\n\t\"listen\": \"\",\r\n\t\"port\": 44322,\r\n\t\"protocol\": \"vless\",\r\n\t\"settings\": \"{\\n  \\\"clients\\\": [\\n    {\\n      \\\"id\\\": \\\"a39c9655-bcbb-43c4-9b3b-ebd8b7ae9e1e\\\",\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"email\\\": \\\"s729v2km\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"totalGB\\\": 0,\\n      \\\"expiryTime\\\": 0,\\n      \\\"enable\\\": true,\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"subId\\\": \\\"n2b9ubaioe06cak8\\\",\\n      \\\"reset\\\": 0\\n    }\\n  ],\\n  \\\"decryption\\\": \\\"none\\\",\\n  \\\"fallbacks\\\": []\\n}\",\r\n\t\"streamSettings\": \"{\\n  \\\"network\\\": \\\"tcp\\\",\\n  \\\"security\\\": \\\"none\\\",\\n  \\\"externalProxy\\\": [],\\n  \\\"tcpSettings\\\": {\\n    \\\"acceptProxyProtocol\\\": false,\\n    \\\"header\\\": {\\n      \\\"type\\\": \\\"none\\\"\\n    }\\n  }\\n}\",\r\n\t\"sniffing\": \"{\\n  \\\"enabled\\\": false,\\n  \\\"destOverride\\\": [\\n    \\\"http\\\",\\n    \\\"tls\\\",\\n    \\\"quic\\\",\\n    \\\"fakedns\\\"\\n  ],\\n  \\\"metadataOnly\\\": false,\\n  \\\"routeOnly\\\": false\\n}\",\r\n\t\"allocate\": \"{\\n  \\\"strategy\\\": \\\"always\\\",\\n  \\\"refresh\\\": 5,\\n  \\\"concurrency\\\": 3\\n}\"\r\n}",
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/update/6",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "update", "6"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:12:37 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "542"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": false,\n    \"msg\": \"Update Failed: record not found\",\n    \"obj\": {\n        \"id\": 6,\n        \"up\": 0,\n        \"down\": 0,\n        \"total\": 0,\n        \"remark\": \"\",\n        \"enable\": true,\n        \"expiryTime\": 0,\n        \"clientStats\": null,\n        \"listen\": \"\",\n        \"port\": 44322,\n        \"protocol\": \"vless\",\n        \"settings\": \"{\\n  \\\"clients\\\": [\\n    {\\n      \\\"id\\\": \\\"a39c9655-bcbb-43c4-9b3b-ebd8b7ae9e1e\\\",\\n      \\\"flow\\\": \\\"\\\",\\n      \\\"email\\\": \\\"s729v2km\\\",\\n      \\\"limitIp\\\": 0,\\n      \\\"totalGB\\\": 0,\\n      \\\"expiryTime\\\": 0,\\n      \\\"enable\\\": true,\\n      \\\"tgId\\\": \\\"\\\",\\n      \\\"subId\\\": \\\"n2b9ubaioe06cak8\\\",\\n      \\\"reset\\\": 0\\n    }\\n  ],\\n  \\\"decryption\\\": \\\"none\\\",\\n  \\\"fallbacks\\\": []\\n}\",\n        \"streamSettings\": \"{\\n  \\\"network\\\": \\\"tcp\\\",\\n  \\\"security\\\": \\\"none\\\",\\n  \\\"externalProxy\\\": [],\\n  \\\"tcpSettings\\\": {\\n    \\\"acceptProxyProtocol\\\": false,\\n    \\\"header\\\": {\\n      \\\"type\\\": \\\"none\\\"\\n    }\\n  }\\n}\",\n        \"tag\": \"\",\n        \"sniffing\": \"{\\n  \\\"enabled\\\": false,\\n  \\\"destOverride\\\": [\\n    \\\"http\\\",\\n    \\\"tls\\\",\\n    \\\"quic\\\",\\n    \\\"fakedns\\\"\\n  ],\\n  \\\"metadataOnly\\\": false,\\n  \\\"routeOnly\\\": false\\n}\",\n        \"allocate\": \"{\\n  \\\"strategy\\\": \\\"always\\\",\\n  \\\"refresh\\\": 5,\\n  \\\"concurrency\\\": 3\\n}\"\n    }\n}"
                }
            ]
        },
        {
            "name": "Update Client",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    },
                    {
                        "key": "Cookie",
                        "value": "",
                        "type": "text",
                        "disabled": true
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\r\n\t\"id\": 20,\r\n\t\"settings\": \"{\\\"clients\\\": [{\\\"id\\\": \\\"6046007d-f4e5-4384-a545-2848165001da\\\",\\\"flow\\\": \\\"\\\",\\\"email\\\": \\\"sbhmrsmz\\\",\\\"limitIp\\\": 0,\\\"totalGB\\\": 10737418240,\\\"expiryTime\\\": 1729073736270,\\\"enable\\\": true,\\\"tgId\\\": \\\"\\\",\\\"subId\\\": \\\"z70791vpexfxw57h\\\",\\\"reset\\\": 0}]}\"\r\n}",
                    "options": {
                        "raw": {
                            "language": "json"
                        }
                    }
                },
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/updateClient/{uuid}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "updateClient",
                        "{uuid}"
                    ]
                },
                "description": "## Description\n\nThis route is used to update an existing client identified by its UUID (`{uuid}`) within a specific inbound.\n\n## **Path Parameter**\n\n- `{uuid}` : Unique identifier (UUID) of the specific client for whom information is being updated.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Verify that the provided `{uuid}` corresponds to an existing client within the system associated with the specified inbound.\n- Format the client information in the `settings` parameter as a stringified JSON format within the request body.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\r\n    \"id\": 3,\r\n    \"settings\": \"{\\\"clients\\\":[{\\\"id\\\":\\\"95e4e7bb-7796-47e7-e8a7-f4055194f776\\\",\\\"alterId\\\":0,\\\"email\\\":\\\"test123\\\",\\\"limitIp\\\":2,\\\"totalGB\\\":42949672960,\\\"expiryTime\\\":1682864675944,\\\"enable\\\":true,\\\"tgId\\\":\\\"\\\",\\\"subId\\\":\\\"\\\"}]}\"\r\n}",
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/updateClient/819920c0-22c8-4c83-8713-9c3da4980396",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "updateClient",
                                "819920c0-22c8-4c83-8713-9c3da4980396"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:13:57 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "83"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Client updated Successfully\",\n    \"obj\": null\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "body": {
                            "mode": "raw",
                            "raw": "{\r\n    \"id\": 3,\r\n    \"settings\": \"{\\\"clients\\\":[{\\\"id\\\":\\\"95e4e7bb-7796-47e7-e8a7-f4055194f776\\\",\\\"alterId\\\":0,\\\"email\\\":\\\"test123\\\",\\\"limitIp\\\":2,\\\"totalGB\\\":42949672960,\\\"expiryTime\\\":1682864675944,\\\"enable\\\":true,\\\"tgId\\\":\\\"\\\",\\\"subId\\\":\\\"\\\"}]}\"\r\n}",
                            "options": {
                                "raw": {
                                    "language": "json"
                                }
                            }
                        },
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/updateClient/fake uuid",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "updateClient",
                                "fake uuid"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:15:08 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "106"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": false,\n    \"msg\": \"Something went wrong! Failed: empty client ID\\n\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Clear Client Ip address",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/clearClientIps/{email}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "clearClientIps",
                        "{email}"
                    ]
                },
                "description": "## Description\n\nThis route is used to reset or clear the IP records associated with a specific client identified by their email address (`{email}`).\n\n## Path Parameter\n\n- `{email}` : Email address of the client for whom IP records need to be reset.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Verify that the provided `{email}` corresponds to an existing client within the system for whom IP records need to be cleared.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Response",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/clearClientIps/27225ost",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "clearClientIps",
                                "27225ost"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:16:28 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "80"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Log Cleared Successfully\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Reset traffics of all inbounds",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/resetAllTraffics",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "resetAllTraffics"]
                },
                "description": "## Description\n\nThis route is used to reset the traffic statistics for all inbounds within the system.\n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Resetting the traffics through this endpoint affects the statistics for all inbounds within the system.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Response",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://localhost:2053/panel/api/inbounds/resetAllTraffics",
                            "protocol": "http",
                            "host": ["localhost"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "resetAllTraffics"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:17:07 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "93"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"all traffic has been reset Successfully\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Reset traffics of all clients in an inbound",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/resetAllClientTraffics/{inboundId}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "resetAllClientTraffics",
                        "{inboundId}"
                    ]
                },
                "description": "## Description\n\nThis route is used to reset the traffic statistics for all clients associated with a specific inbound identified by its ID (`{inboundId}`).\n\n## Path Parameter\n\n- `{inboundId}` : Identifier of the specific inbound for which client traffics are being reset.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Resetting the client traffics through this endpoint affects all clients associated with the specified inbound.\n- Verify that the provided `{inboundId}` corresponds to an existing inbound within the system.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Response",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/resetAllClientTraffics/3",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "resetAllClientTraffics",
                                "3"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:17:39 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "107"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"All traffic from the client has been reset. Successfully\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Reset Client Traffic",
            "request": {
                "method": "POST",
                "header": [],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/{inboundId}/resetClientTraffic/{email}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "{inboundId}",
                        "resetClientTraffic",
                        "{email}"
                    ]
                },
                "description": "## Description\n\nThis route is used to reset the traffic statistics for a specific client identified by their email address (`{email}`) within a particular inbound identified by its ID (`{inboundId}`).\n\n## Path Parameters\n\n- `{inboundId}` : Identifier of the specific inbound where the client belongs.\n- `{email}` : Email address of the client for whom traffic statistics are being reset.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Resetting the client traffic through this endpoint affects the statistics for the specified client within the specified inbound.\n- Verify that the provided {inboundId} corresponds to an existing inbound and `{email}` corresponds to an existing client within the system.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Response",
                    "originalRequest": {
                        "method": "POST",
                        "header": [],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/3/resetClientTraffic/27225ost",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "3",
                                "resetClientTraffic",
                                "27225ost"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:18:46 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "91"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Traffic has been reset Successfully\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Delete Client",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/{inboundId}/delClient/{uuid}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "{inboundId}",
                        "delClient",
                        "{uuid}"
                    ]
                },
                "description": "## Description\n\nThis route is used to delete a client identified by its UUID (`{uuid}`) within a specific inbound identified by its ID (`{inboundId}`).\n\n## Path Parameters\n\n- `{inboundId}` : Identifier of the specific inbound from which the client will be deleted.\n- `{uuid}` : Unique identifier (UUID) of the specific client to be deleted.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Ensure that the provided `{inboundId}` corresponds to an existing inbound and `{uuid}` corresponds to an existing client within the system.\n- Deleting the client through this endpoint permanently removes the specified client from the specified inbound.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/3/delClient/bf036995-a81d-41b3-8e06-8e233418c96a",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "3",
                                "delClient",
                                "bf036995-a81d-41b3-8e06-8e233418c96a"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:20:24 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "83"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Client deleted Successfully\",\n    \"obj\": null\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/2/delClient/95e2b7bb-7796-47e7-e8a7-f4055194f433",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "2",
                                "delClient",
                                "95e2b7bb-7796-47e7-e8a7-f4055194f433"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:20:48 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "101"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": false,\n    \"msg\": \"Something went wrong! Failed: record not found\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Delete Inbound",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/del/{inboundId}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "del", "{inboundId}"]
                },
                "description": "## Description\n\nThis route is used to delete an inbound identified by its ID (`{inboundId}`).\n\n## Path Parameter\n\n- `{inboundId}` : Identifier of the specific inbound to be deleted.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- Ensure that the provided `{inboundId}` corresponds to an existing inbound within the system.\n- Deleting the inbound through this endpoint permanently removes the specified inbound.\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Successful",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/del/3",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "del", "3"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:21:26 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "72"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"Delete Successfully\",\n    \"obj\": 3\n}"
                },
                {
                    "name": "Failed",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/del/3",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "del", "3"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:21:34 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "89"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": false,\n    \"msg\": \"Delete Failed: record not found\",\n    \"obj\": 3\n}"
                }
            ]
        },
        {
            "name": "Delete Depleted Clients",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Accept",
                        "value": "application/json",
                        "type": "text"
                    }
                ],
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/delDepletedClients/{inboundId}",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": [
                        "panel",
                        "api",
                        "inbounds",
                        "delDepletedClients",
                        "{inboundId}"
                    ]
                },
                "description": "## Description\n\nThis route is used to delete all depleted clients associated with a specific inbound identified by its ID (`{inboundId}`). If no `{inboundId}` is specified, depleted clients will be deleted from all inbounds.\n\n## Path Parameter\n\n- `{inboundId}` : Identifier of the specific inbound from which the depleted clients will be deleted. If not specified, depleted clients will be deleted from all inbounds.\n    \n\n## Note\n\n- Requires a valid session ID (from the login endpoint). Include the session ID stored in the cookie named \"session\" for authorization.\n    \n- If `{inboundId}` is provided, ensure it corresponds to an existing inbound within the system. If not provided, depleted clients will be deleted from all inbounds.\n- Deleting depleted clients through this endpoint permanently removes all depleted clients from the specified inbound(s).\n- Handle any potential errors or failure messages returned in the response."
            },
            "response": [
                {
                    "name": "Response",
                    "originalRequest": {
                        "method": "POST",
                        "header": [
                            {
                                "key": "Accept",
                                "value": "application/json",
                                "type": "text"
                            }
                        ],
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/delDepletedClients/4",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": [
                                "panel",
                                "api",
                                "inbounds",
                                "delDepletedClients",
                                "4"
                            ]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:22:23 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "97"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"All depleted clients are deleted Successfully\",\n    \"obj\": null\n}"
                }
            ]
        },
        {
            "name": "Online Clients",
            "event": [
                {
                    "listen": "prerequest",
                    "script": {
                        "exec": [""],
                        "type": "text/javascript",
                        "packages": {}
                    }
                }
            ],
            "request": {
                "method": "POST",
                "header": [],
                "body": {
                    "mode": "formdata",
                    "formdata": []
                },
                "url": {
                    "raw": "http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/panel/api/inbounds/onlines",
                    "protocol": "http",
                    "host": ["{{HOST}}"],
                    "port": "{{PORT}}{{WEBBASEPATH}}",
                    "path": ["panel", "api", "inbounds", "onlines"]
                }
            },
            "response": [
                {
                    "name": "Response",
                    "originalRequest": {
                        "method": "POST",
                        "header": [],
                        "body": {
                            "mode": "formdata",
                            "formdata": []
                        },
                        "url": {
                            "raw": "http://{{HOST}}:2053/panel/api/inbounds/onlines",
                            "protocol": "http",
                            "host": ["{{HOST}}"],
                            "port": "2053",
                            "path": ["panel", "api", "inbounds", "onlines"]
                        }
                    },
                    "status": "OK",
                    "code": 200,
                    "_postman_previewlanguage": "json",
                    "header": [
                        {
                            "key": "Content-Encoding",
                            "value": "gzip"
                        },
                        {
                            "key": "Content-Type",
                            "value": "application/json; charset=utf-8"
                        },
                        {
                            "key": "Vary",
                            "value": "Accept-Encoding"
                        },
                        {
                            "key": "Date",
                            "value": "Thu, 17 Oct 2024 14:26:49 GMT"
                        },
                        {
                            "key": "Content-Length",
                            "value": "68"
                        }
                    ],
                    "cookie": [],
                    "body": "{\n    \"success\": true,\n    \"msg\": \"\",\n    \"obj\": [\n        \"88vzckui\"\n    ]\n}"
                }
            ]
        }
    ],
    "event": [
        {
            "listen": "prerequest",
            "script": {
                "type": "text/javascript",
                "exec": [""]
            }
        },
        {
            "listen": "test",
            "script": {
                "type": "text/javascript",
                "exec": [""]
            }
        }
    ],
    "variable": [
        {
            "key": "HOST",
            "value": "localhost",
            "type": "string"
        },
        {
            "key": "PORT",
            "value": "2053",
            "type": "string"
        },
        {
            "key": "WEBBASEPATH",
            "value": "",
            "type": "string"
        },
        {
            "key": "USERNAME",
            "value": "admin",
            "type": "string"
        },
        {
            "key": "PASSWORD",
            "value": "admin",
            "type": "string"
        }
    ]
}
