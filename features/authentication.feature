Feature: Authenticate

  Scenario: Login success
    Given the user is loaded:
      | email    | bob@coopcycle.org |
      | username | bob               |
      | password | 123456            |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/login_check" with parameters:
      | key       | value  |
      | _username | bob    |
      | _password | 123456 |
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
      """
      {
        "token": @string@,
        "roles": @array@,
        "username": "bob",
        "email": "bob@coopcycle.org",
        "id": @integer@,
        "refresh_token": @string@,
        "enabled": true
      }
      """

  Scenario: Login by email success
    And the user is loaded:
      | email    | bob@coopcycle.org |
      | username | bob               |
      | password | 123456            |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/login_check" with parameters:
      | key       | value  |
      | _username | bob@coopcycle.org    |
      | _password | 123456 |
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "token": @string@,
      "roles": @array@,
      "username": "bob",
      "email": "bob@coopcycle.org",
      "id": @integer@,
      "refresh_token": @string@,
      "enabled": true
    }
    """

  Scenario: Login failure
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/login_check" with parameters:
      | key       | value  |
      | _username | nope   |
      | _password | 123456 |
    Then the response status code should be 401
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "code": 401,
      "message": @string@
    }
    """

  Scenario: Authenticated request
    Given the user is loaded:
      | email    | bob@coopcycle.org |
      | username | bob               |
      | password | 123456            |
    And the user "bob" is authenticated
    When I add "Accept" header equal to "application/ld+json"
    And the user "bob" sends a "GET" request to "/api/me"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "@context": "/api/contexts/User",
      "@id": "/api/users/1",
      "@type": "User",
      "addresses": [],
      "username": "bob",
      "email": "bob@coopcycle.org",
      "roles":["ROLE_USER"]
    }
    """

  Scenario: Register success
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/register" with parameters:
      | key         | value             |
      | _email      | bob@coopcycle.org |
      | _username   | bob               |
      | _password   | 123456            |
      | _givenName  | Bob               |
      | _familyName | Doe               |
      | _telephone  | +33612345678      |
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "id":@integer@,
      "roles":[
        "ROLE_USER"
      ],
      "username":"bob",
      "email":"bob@coopcycle.org",
      "enabled": true,
      "token":@string@,
      "refresh_token":@string@
    }
    """

  Scenario: Register failure
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/register" with parameters:
      | key         | value             |
      | _email      | bob@coopcycle.org |
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "type":"https://tools.ietf.org/html/rfc2616#section-10",
      "title":@string@,
      "detail":@string@,
      "violations":[
        {
          "propertyPath":"data.username",
          "message":@string@
        },
        {
          "propertyPath":"data.givenName",
          "message":@string@
        },
        {
          "propertyPath":"data.familyName",
          "message":@string@
        },
        {
          "propertyPath":"data.telephone",
          "message":@string@
        }
      ]
    }
    """

  Scenario: Confirm registration success
    Given the user is loaded:
      | email             | bob@coopcycle.org |
      | username          | bob               |
      | password          | 123456            |
      | enabled           | false             |
      | confirmationToken | 123456            |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/register/confirm/123456"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "id":@integer@,
      "roles":[
        "ROLE_USER"
      ],
      "username":"bob",
      "email":"bob@coopcycle.org",
      "enabled": true,
      "token":@string@,
      "refresh_token":@string@
    }
    """

  Scenario: Confirm registration failure
    Given the user is loaded:
      | email             | bob@coopcycle.org |
      | username          | bob               |
      | password          | 123456            |
      | enabled           | false             |
      | confirmationToken | 123456            |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/register/confirm/654321"
    Then the response status code should be 401
    And the response should be in JSON

  Scenario: Reset request success
    Given the user is loaded:
      | email    | bob@coopcycle.org |
      | username | bob               |
      | password | 123456            |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/resetting/send-email" with parameters:
      | key         | value             |
      | username    | bob               |
    Then the response status code should be 202
    And the response should be in JSON
    And the JSON should match:
    """
    {
    }
    """

  Scenario: Reset request failure, user does not exist
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/resetting/send-email" with parameters:
      | key         | value             |
      | username    | bob               |
    Then the response status code should be 202
    And the response should be in JSON
    And the JSON should match:
    """
    {
    }
    """

  Scenario: Set new password success
    Given the user is loaded:
      | username            | bob               |
      | email               | bob@coopcycle.org |
      | givenName           | Bob               |
      | familyName          | Flansburg         |
      | telephone           | +33144781233      |
      | password            | 123456            |
      | confirmationToken   | 123456            |
      | passwordRequestAge  | 60                |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/resetting/reset/123456" with parameters:
      | key         | value             |
      | password    | 654321            |
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "id":@integer@,
      "roles":[
        "ROLE_USER"
      ],
      "username":"bob",
      "email":"bob@coopcycle.org",
      "enabled": true,
      "token":@string@,
      "refresh_token":@string@
    }
    """

  Scenario: Set new password failure, token expired
    Given the user is loaded:
      | username            | bob               |
      | email               | bob@coopcycle.org |
      | givenName           | Bob               |
      | familyName          | Flansburg         |
      | telephone           | +33144781233      |
      | password            | 123456            |
      | confirmationToken   | 123456            |
      | passwordRequestAge  | 86410             |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/resetting/reset/123456" with parameters:
      | key         | value             |
      | password    | 654321            |
    Then the response status code should be 400
    And the response should be in JSON
    """
    {
      "@context":"/api/contexts/Error",
      "@type":"hydra:Error",
      "hydra:title":"An error occurred",
      "hydra:description":@string@,
      "trace":@array@
    }
    """

  Scenario: Set new password failure, wrong token
    Given the user is loaded:
      | username            | bob               |
      | email               | bob@coopcycle.org |
      | givenName           | Bob               |
      | familyName          | Flansburg         |
      | telephone           | +33144781233      |
      | password            | 123456            |
      | confirmationToken   | 123456            |
      | passwordRequestAge  | 60                |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/resetting/reset/wrong_token" with parameters:
      | key         | value             |
      | password    | 654321            |
    Then the response status code should be 401
    And the response should be in JSON
    """
    {
      "@context":"/api/contexts/Error",
      "@type":"hydra:Error",
      "hydra:title":"An error occurred",
      "hydra:description":@string@,
      "trace":@array@
    }
    """
