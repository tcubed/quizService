# quizService
This project is meant to decouple quiz "data" from the user-experience.

## JumpJock
This self-study tool provides questions at a metered pace, allowing
quizzers to "jump", interrupting the question, and try to answer.

### API
The application programming interface (API) allows access to questions
through a couple of means:

 - random question: by providing a starting and an ending verse,
   a random question within that range is returned.  Optionally, 
   the question type can be provided.
 - question index: this is a numerical index of the question based
   on the database provided by the CQLT.